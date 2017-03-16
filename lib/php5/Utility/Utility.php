<?php

class Utility
{
	protected static $sort_key 	= 'sort_value';
	protected static $table_cache = array();

	public static $command_line  = false;
	public static $debug_mode    = false;
	public static $file_override = false;

	/**
	 * Array Unique - Provided as a faster implimentation of array_unique.
	 *	http://us.php.net/manual/en/function.array-unique.php#77743
	 *
	 * @param array_in array
	 */
	public static function arrayUnique($array_in)
	{
		return array_filter(array_keys(array_flip($array_in)));
	}

	public static function getTmpDir($prefix, $file_name = null, $file_extension = null) {
		$tmp_dir                = '/tmp/'.uniqid($prefix);
		mkdir($tmp_dir);

		if ($file_extension) {$user_email = explode('@', User::$current['email']);$tmp_dir = $tmp_dir.'/'.$user_email[0].'_'.date('Ymd').'_'.date('H_i_s').$file_extension;touch($tmp_dir);}
		if ($file_name)      {$tmp_dir    = $tmp_dir.'/'.$file_name;touch($tmp_dir);}

		return $tmp_dir;
	}

	/**
	 * Throw if no
	 *
	 * @extern true
	 */
	public static function throwIfNo($test, $name = null, $msg = null, $backtrace = null)
	{
		if (!$test)
		{
			$backtrace = $backtrace ? $backtrace : debug_backtrace();
			extract(array_shift($backtrace), EXTR_PREFIX_ALL, 'error');
			extract(array_shift($backtrace), EXTR_PREFIX_ALL, 'caller');
			throw new Exception("$caller_class::$caller_function@$error_line - ".($msg ? $msg : ($name ? "Invalid value for $name." : 'Unable to complete operation.')));
		}
	}

	public static function sortByValue($array, $value, $order = 'ASC', $bStable = false)
	{
		Utility::$sort_key = $value;

		if ($bStable)
		{
			Utility::mergesort($array, 'Utility::compareSortValues');
		}
		else
		{
			uasort($array, 'Utility::compareSortValues');
		}

		if ($order == 'ASC')
		{
			return $array;
		}
		else
		{
			return array_reverse($array);
		}
	}

	/**
	 * Compare sort values - used in sortBySortValue.
	 *
	 * @extern true
	 */
	public static function compareSortValues($a, $b)
	{
		if ($a[Utility::$sort_key] == $b[Utility::$sort_key])
		{
			return 0;
		}
		else
		{
			return ($a[Utility::$sort_key] < $b[Utility::$sort_key]) ? -1 : 1;
		}
	}

	public static function mergesort(&$array, $cmp_function = 'strcmp') {
	    // Arrays of size < 2 require no action.
	    if (count($array) < 2) return;
	    // Split the array in half
	    $halfway = count($array) / 2;
	    $array1 = array_slice($array, 0, $halfway);
	    $array2 = array_slice($array, $halfway);
	    // Recurse to sort the two halves
	    Utility::mergesort($array1, $cmp_function);
	    Utility::mergesort($array2, $cmp_function);
	    // If all of $array1 is <= all of $array2, just append them.
	    if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
	        $array = array_merge($array1, $array2);
	        return;
	    }
	    // Merge the two sorted arrays into a single sorted array
	    $array = array();
	    $ptr1 = $ptr2 = 0;
	    while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
	        if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
	            $array[] = $array1[$ptr1++];
	        }
	        else {
	            $array[] = $array2[$ptr2++];
	        }
	    }
	    // Merge the remainder
	    while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
	    while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];
	    return;
	}

	/**
	 * Sort by sort_value - accepts an array with a sort_value key on each item. Returns sorted array.
	 *
	 * @extern true
	 */
	public static function sortBySortValue($array, $order = 'ASC', $bPreserveIndexes = false)
	{
		Utility::$sort_key = 'sort_value';

		if ($bPreserveIndexes)
		{
			uasort($array, 'Utility::compareSortValues');
		}
		else
		{
			usort($array, 'Utility::compareSortValues');
		}

		if ($order == 'ASC')
		{
			return $array;
		}
		else
		{
			return array_reverse($array);
		}
	}

	/**
	 * Assemble SQL
	 *
	 * @extern true
	 */
	public static function assembleSql($sqlComponents)
	{
		$sql = "";

		$sqlComponents['selects']	= array_unique($sqlComponents['selects']);
		$sqlComponents['froms'] 	= array_unique($sqlComponents['froms']);
		$sqlComponents['joins']		= array_unique($sqlComponents['joins']);
		$sqlComponents['wheres']	= array_unique($sqlComponents['wheres']);
		$sqlComponents['groupbys']	= array_unique($sqlComponents['groupbys']);
		$sqlComponents['orderbys']	= array_unique($sqlComponents['orderbys']);

		// Remove unnecessary joins - these joins can cause major inefficiencies
		$expensive_joins = array(
			'filter_ol',	// Can cause a 50x increase in runtime
			'filter_opd'	// Can cause a 2x increase in runtime
		);

		// See which of the joins are actually necessary by examining the wheres and selects
		$query_test = implode(' AND ', $sqlComponents['wheres']) . implode(' AND ', $sqlComponents['selects']);

		foreach($expensive_joins as $alias)
		{
			if(!preg_match('/\s' . $alias . '\./', $query_test))
			{
				// Alias is not used, remove it from joins
				for($i = 0; $i < count($sqlComponents['joins']); $i++)
				{
					if(preg_match('/\s' . $alias . '\s+ON/', $sqlComponents['joins'][$i]))
					{
						// Remove join from list
						array_splice($sqlComponents['joins'], $i, 1);

						break;
					}
				}
			}
		}

		if ($sqlComponents['selects'] &&
			count($sqlComponents['selects']))
		{
			$sql .= "SELECT".($sqlComponents['distinct'] ? " DISTINCT" : "")."\n\t".implode(",\n\t", $sqlComponents['selects'])."\n\n";
		}
		else if (
			$sqlComponents['froms'] &&
			count($sqlComponents['froms']))
		{
			$sql .= "SELECT".($sqlComponents['distinct'] ? " DISTINCT" : "")."\n\t*\n\n";
		}
		else
		{
			throw new Exception('Utility::assembleSql() - Invalid SQL component combination.');
		}

		if ($sqlComponents['froms'] &&
			count($sqlComponents['froms']))
		{
			$sql .= "FROM\n\t".implode(",\n\t", $sqlComponents['froms'])."\n\n";
		}

		if ($sqlComponents['joins'] &&
			count($sqlComponents['joins']))
		{
			$sql .= implode("\n", $sqlComponents['joins'])."\n\n";
		}

		if ($sqlComponents['wheres'] &&
			count($sqlComponents['wheres']))
		{
			$sql .= "WHERE\n\t".implode(" AND\n\t", $sqlComponents['wheres'])."\n\n";
		}

		if ($sqlComponents['groupbys'] &&
			count($sqlComponents['groupbys']))
		{
			$sql .= "GROUP BY\n\t".implode(",\n\t", $sqlComponents['groupbys'])."\n\n";
		}

		if ($sqlComponents['orderbys'] &&
			count($sqlComponents['orderbys']))
		{
			$sql .= "ORDER BY\n\t".implode(",\n\t", $sqlComponents['orderbys'])."\n\n";
		}

		if ($sqlComponents['limit'])
		{
			$sql .= "LIMIT\n\t".$sqlComponents['limit']."\n\n";
		}

		if ($sqlComponents['offset'])
		{
			$sql .= "OFFSET\n\t".$sqlComponents['offset'];
		}

		return $sql;
	}

	/**
	 * Extract values by key
	 *
	 * @extern true
	 */
	public static function extractValuesByKey($key, $sourceArray)
	{
		$returnArray = array();

		foreach ($sourceArray as $member)
		{
			if (is_array($key))
			{
				$tempArray = array();

				foreach ($key as $old_key => $new_key)
				{
					if (isset($member[$old_key]))
					{
						$tempArray[$new_key] = $member[$old_key];
					}
				}

				$returnArray[] = $tempArray;
			}
			else
			{
				if (isset($member[$key]))
				{
					$returnArray[] = $member[$key];
				}
			}
		}

		return $returnArray;
	}

	/**
	 * Delete duplicates by key
	 *
	 * @extern true
	 */
	public static function deleteDuplicateValuesByKey($key, $sourceArray)
	{
		$returnArray	= array();
		$foundKeyValues	= array();

		foreach ($sourceArray as $member)
		{
			if (!$foundKeyValues[$member[$key]])
			{
				$foundKeyValues[$member[$key]] = true;
				$returnArray[] = $member;
			}
		}

		return $returnArray;
	}

	public static function array_find_recursive($needle, $haystack, $partial_matches = false, $search_keys = false)
	{
		if (!is_array($haystack))
		{
			return false;
		}

		foreach ($haystack as $key => $value)
		{
			$current = ($search_keys) ? $key : $value;

			if ($current === $needle)
			{
				return $key;
			}
			else if ($partial_matches && @strpos($current, $needle) !== false)
			{
				return $key;
			}
			else if (is_array($value) && Utility::array_find_recursive($needle, $value, $partial_matches, $search_keys) !== false)
			{
				return $key;
			}
		}

		return false;
	}

	/**
	 * Group subarrays by key
	 *
	 * @extern true
	 */
	public static function groupSubArraysByKey($array, $key)
	{
		$returnArray = array();

		if (!is_array($key))
		{
			$key = array($key);
		}

		foreach ($array as $subArray)
		{
			$sortValue = $subArray;

			foreach ($key as $keyArray)
			{
				$sortValue = $sortValue[$keyArray];
			}

			$returnArray[$sortValue][] = $subArray;
		}

		return $returnArray;
	}

	/**
	 * Sum subarrays by key
	 *
	 * @extern true
	 */
	public static function sumSubArraysByKey($array, $key)
	{
		$sum = 0;

		foreach ($array as $subArray)
		{
			$sum += $subArray[$key];
		}

		return $sum;
	}

	/**
	 * Prepare temp statement
	 *
	 * @extern true
	 */
	public static function prepareTempStatement($sql)
	{
		$name = uniqid('prst');

		Gateway::prepareStmt($name, $sql);

		return $name;
	}

	/**
	 * Execute safe SQL - uses temp prepared statement
	 *
	 * @extern true
	 */
	public static function executeSafeSQL($sql, $params, $print = false, $execute = true)
	{
		// Implode any params that are arrays
		foreach ($params as $paramName => $paramValue)
		{
			if (is_array($paramValue))
			{
				$params[$paramName] = implode(', ', $paramValue);
			}
		}

		if ($print)
		{
			print "<pre>SQL: ";
			print_r($sql);
			print "</pre>";

			print "<pre>Params: ";
			print_r($params);
			print "</pre>";
		}

		return $execute ? pg_execute(Gateway::getSqlConn(), Utility::prepareTempStatement($sql), $params) : null;
	}

	/**
	 * Function: pgQueryParams
	 *
	 * Parameters:
	 * $sql
	 * $params
	 *
	 * Returns:
	 * results
	 *
	 * @extern true
	 */
	public static function pgQueryParams($sql, $params, $suppress = false)
	{
		// Rewrite query with WHERE INs made happy
		$new_params = array();

		foreach ($params as $index => $param)
		{
			$param_index = count($new_params) + 1;

			if (is_array($param) && strpos($sql, '($'.$param_index.')') !== false)
			{
				$sql = str_replace('($'.$param_index.')', '('.implode(', ', $param).')', $sql);

				// Decrement any params in the sql whose index is greater than the current param
				for ($i = $param_index + 1; $i <= count($params) + 1; $i++)
				{
					$sql = str_replace('$'.$i, '$'.($i-1), $sql);
				}
			}
			else
			{
				$new_params[] = $param;
			}
		}

		$params = $new_params;

		// Execute query
		$conn = Gateway::getSqlConn();
		$sqh  = pg_query_params($conn, $sql, $params);

		if ($sqh)
		{
			$rows = pg_fetch_all($sqh);
			return ($rows ? $rows : array());
		}
		else if (!$suppress)
		{
			$error = pg_last_error($conn);
			Utility::throwIfNo(false, '', "Encountered error while executing SQL ($sql): $error");
		}
	}

	public static function removeEmptyValues($array)
	{
		if (!is_array($array))
		{
			return false;
		}

		$empty_elements = array("");

		foreach ($array as $index => $value)
		{
			if (is_array($index))
			{
				$array[$index] = Utility::removeEmptyValues($array[$index]);
			}
			else
			{
				$array[$index]	= array_diff($array[$index],$empty_elements);
			}
		}

		return $array;
	}

	public static function recursiveUnset(&$array, $unwanted_key)
	{
		unset($array[$unwanted_key]);

		foreach ($array as &$value)
		{
			if (is_array($value))
			{
				Utility::recursiveUnset($value, $unwanted_key);
			}
		}
	}

	public static function moveValueByIndex($array, $from = null, $to = null)
	{
		$result_array = array();
		$from_ct = 0;
		foreach ( $array as $key => $value )
		{
			if ( $from_ct == $from )
			{
				$piece = array($key=>$value);
				unset($array[$key]);
			}

			$from_ct++;
		}

		if ( $to + 1 == count($array) )
		{
			foreach ( $array as $key => $value )
			{
				$result_array = array_merge($result_array, array($key=>$value));
			}

			return array_merge($result_array, $piece);
		}

		$to_ct = 0;
		foreach ( $array as $key => $value )
		{
			if ( $to_ct == $to )
			{
				$result_array = array_merge($result_array, $piece);
			}

			$result_array = array_merge($result_array, array($key=>$value));

			$to_ct++;
		}

		return $result_array;
	}

	public static function swapKeyNames(&$array, $from, $to)
	{
		$result_array = array();
		foreach ( $array as $key => $value )
		{
			if ( $key == $from )
			{
				$result_array = array_merge($result_array, array($to=>$value));
			}
			else
			{
				$result_array = array_merge($result_array, array($key=>$value));
			}
		}

		$array = $result_array;
	}

	/**
	 * Function: array_truncate
	 * Truncates an array to a given depth.
	 *
	 * Parameters:
	 * array
	 * max_depth
	 * message
	 * current_depth
	 *
	 * Returns:
	 * depth-truncated array
	 */
	public static function array_truncate($array, $max_depth = 10, $message = '(array truncated)', $current_depth = 0)
	{
		if (!is_array($array) && !is_object($array))	{return $array;}
		if ($current_depth >= $max_depth)				{return $message;}
		$copy = array();
		foreach ($array as $key => $value) {$copy[$key] = Utility::array_truncate($value, $max_depth, $message, $current_depth + 1);}
		return $copy;
	}

	/**
	 * Function: uprint
	 * User-defined print() alternative. Slightly more compact output, with auto-labelling and depth truncation.
	 *
	 * Parameters:
	 * array - array to print (works fine with strings/numbers too)
	 * label - label to prepend to the output
	 * max_depth - array is depth-truncated to this
	 *
	 * Returns:
	 * preformatted string
	 */
	public static function uprint($array, $label = null, $max_depth = 10)
	{
		if (is_string($array))
		{
			$string = $array;
		}
		else
		{
			$array  = $max_depth ? self::array_truncate($array, $max_depth) : $array;
			$string = preg_replace('/Array[\s\n\t]*/', 'Array ', print_r($array, true));
		}

		if (Utility::$command_line)
		{
			print "\n";
			print ($label ? "$label: " : '');
			print_r($string);
			// print "\n";
		}
		else if (Utility::$file_override)
		{
			$output  = "\n";
			$output .= ($label ? "$label: " : '');
			$output .= print_r($string, true);
			file_put_contents(Utility::$file_override, $output, FILE_APPEND);
		}
		else
		{
			print "<pre>".($label ? "$label: " : '');
			print_r($string);
			print "</pre>";
		}
	}

	/**
	 * Function: printLine
	 *
	 * @extern true
	 */
	public static function printLine()
	{
		// print __LINE__ . "<br>";
	}

	/**
	 * Function: printDebug
	 *
	 * @extern true
	 */
	public static function printDebug($array, $label = null, $max_depth = 10)
	{
		if (Utility::$debug_mode)
		{
			Utility::uprint($array, $label, $max_depth);
		}
	}

	/**
	 * Function: setDefault
	 * Sets $value to $default if it is not already set.
	 *
	 * Parameters:
	 * value - (passed by reference)
	 * default - default value to assign
	 *
	 * Returns:
	 * null (value is modified in-place)
	 */
	public static function setDefault(&$value, $default)
	{
		if (!$value && $value !== 0 && $value !== false)
		{
			$value = $default;
		}
	}

	/*
	  Generate test function

	  @extern true
	 */
	public static function generateTestFunction($method_name, $args)
	{
		list($class_name, $function_name) = explode('::', $method_name);
		$function_name = ucfirst($function_name);

		$function_text =
			"\t"."/*"															."\n".
			"\t"."  Test $function_name"										."\n".
			"\t"." "															."\n".
			"\t"."  @extern true"												."\n".
			"\t"." */"															."\n".
			"\t"."public static function test$function_name()"					."\n".
			"\t"."{"															."\n".
			"\t"."\t"."\$args = ".var_export($args, true).";"					."\n".
			""																	."\n".
			"\t"."\t"."return call_user_func_array('$method_name', \$args);"	."\n".
			"\t"."}";

		return $function_text;
	}

	public static function rrmdir($dir)
	{
		foreach (glob("$dir/*") as $file)
		{
			if (is_dir($file))
			{
				Utility::rrmdir($file);
			}
			else
			{
				unlink($file);
			}
		}

		rmdir($dir);
	}

	/**
	 * Function: getChain
	 *
	 * Parameters:
	 * $series
	 *
	 * Returns:
	 * array of objects from top to bottom
	 *
	 * @extern true
	 */
	public static function getChain($series)
	{
		$series = explode('/', trim($series, '/'));
		$key    = array_pop($series);
		$abbr   = rtrim($key, '0..9');
		$id     = ltrim($key, 'a..z');

		switch ($abbr)
		{
			case 'pr': $table = 'profile';     break;
			case 'fg': $table = 'field_group'; break;
			case 'f':  $table = 'field';       break;
			case 'r':  $table = 'raster';      break;
		}

		$row = Heresy::selectOne('*', $table, array($table.'_id' => $id));

		if (!count($series))
		{
			return array($row);
		}

		$ancestors   = Utility::getChain(implode('/', $series));
		$ancestors[] = $row;

		return $ancestors;
	}

	/**
	 * Function: getChainString
	 *
	 * Parameters:
	 * $series
	 *
	 * Returns:
	 * $chain_string
	 *
	 * @extern true
	 */
	public static function getChainString($series)
	{
		$chain = Utility::getChain($series);

		if (count($chain) == 1)
		{
			return $chain[0]['name'];
		}

		// array_pop($chain);
		array_shift($chain);
		$names        = Utility::extractValuesByKey('name', $chain);
		$chain_string = implode(' &rarr; ', $names);

		return $chain_string;
	}

	/**
	 * Function: xmlEncode
	 *  Recursively encodes the given data as XML.
	 *
	 * Parameters:
	 *  (string) tag - Tag name for the data
	 *  (array/string/int) data - Object to xml encode
	 *  (int) depth - Recursion depth - only used when this function calls itself
	 *
	 * Returns:
	 *  (string) xml - XML data string representing the input data
	 *
	 * @extern true
	 */
	public static function xmlEncode($tag, $data, $depth = 0, $xml_writer = null)
	{
		if ($depth > 15)
		{
			// Avoid infinite recursion
			return;
		}

		if (!$xml_writer)
		{
			// Start document
			$xml_writer = new XMLWriter();
			$xml_writer->openMemory();
			$xml_writer->setIndent(true);
			$xml_writer->startDocument('1.0', 'UTF-8');
		}

		// Clean up the tag name - remove any inappropriate characters
		$tag = preg_replace('/[^a-zA-Z0-9\_]/', '', $tag);

		// Start tag
		$xml_writer->startElement($tag);

		foreach ($data['attributes'] as $key => $value)
		{
			$xml_writer->writeAttribute($key, $value);
		}

		if (is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (is_numeric($key))
				{
					// Use a generic 'item' tag for arrays
					$key = 'item';
				}

				Utility::xmlEncode($key, $value, $depth + 1, $xml_writer);
			}
		}
		else
		{
			if (is_bool($data))
			{
				$data = $data ? 'true' : 'false';
			}

			$xml_writer->text("$data");
		}

		// Close tag
		$xml_writer->endElement();

		if ($depth == 0)
		{
			// Close document
			$xml_writer->endDocument();
			$xml = $xml_writer->outputMemory();
			return $xml;
		}
	}

	/**
	 * Make csv from data array
	 *
	 * @extern true
	 */
	public static function createCsvFromData($data_array, $file_name, $column_keys = null, $has_header = true, $format = 'csv', $bUseExactFilename = false, $column_config = array())
	{
		// Extract the unique column names from the associative data_array
		$keys = array();
		foreach ( $data_array as $row )
		{
			if ( array_diff(array_keys($row), $keys) )
			{
				$keys = array_merge($keys, array_diff(array_keys($row), $keys));
			}
		}

		if ( $column_keys )
		{
			$keys = $column_keys;
		}

		// Make a csv writer readable column array
		$col_cfg  = array();
		$position = 0;
		foreach ( $keys as $key )
		{
			$col_cfg[] = array("position"=>$position++, "name"=> ucwords(str_replace('_', ' ', $key)), "source"=>$key);
		}

		if ( $column_config )
		{
			$col_cfg = $column_config;
		}

		if ( $format == 'csv' )
		{
			$writer = new CsvWriter(array("has_header"=>$has_header, "columns"=>$col_cfg));
			if ( !$bUseExactFilename )
			{
				$file_name = '/tmp/' . $file_name . '-' . date('Y-m-d') . '-' . uniqid() . '.csv';
			}
			else
			{
				$file_name = '/tmp/' . $file_name . '.csv';
			}
		}
		else
		{
			$writer = new XlsWriter(array("has_header"=>$has_header, "columns"=>$col_cfg));
			if ( !$bUseExactFilename )
			{
				$file_name = '/tmp/' . $file_name . '-' . date('Y-m-d') . '-' . uniqid() . '.xls';
			}
			else
			{
				$file_name = '/tmp/' . $file_name . '.xls';
			}
		}

		$writer->open($file_name);

		foreach ( $data_array as $record )
		{
			$writer->writeRecord($record);
		}

		$writer->close();

		return Utility::successTrue($file_name, 'Successfully created file.');

		// // Create it in the filysys
		// $filesys_info              = Filesys::setCurrentFile($file_name)->toArray();
		// $filesys_info['file_name'] = $file_name;

		// return array(
		// 	'success'      => true,
		// 	'filesys_id'   => $filesys_info['filesys_id'],
		// 	'filesys_info' => $filesys_info
		// 	// 'bDownload' => true
		// );
	}

	/**
	 * Function: uuid
	 *
	 * Returns:
	 * $uuid
	 *
	 * @extern true
	 */
	public static function uuid()
	{
		mt_srand((double)microtime()*10000);

		$charid = strtoupper(md5(uniqid(rand(), true)));

		$uuid = substr($charid,  0, 8).'-'.
		        substr($charid,  8, 4).'-'.
		        substr($charid, 12, 4).'-'.
		        substr($charid, 16, 4).'-'.
		        substr($charid, 20,12);

		$uuid = strtolower($uuid);

		return $uuid;
	}

	/**
	 * Function: getCommaString
	 *
	 * Returns:
	 * string
	 *
	 * @extern true
	 */
	public static function getCommaString($strings)
	{
		// If there aren't any items, something went wrong :(... hopefully this doesn't happen
		if (!count($strings)){return "";}

		// If there is only one thing just return it
		if (count($strings) === 1){return $strings[0];}

		// If there are exactly TWO items put an and in between them and return them
		if (count($strings) === 2){return implode(' and ', $strings);}

		// Lastly, if there are more than two items, separate them with commas and the last item should be ", and last item"
		// Splice out the last one
		$lastItem    = array_pop($strings);
		$commaString = implode(', ', $strings);

		return $commaString . ', and ' . $lastItem;
	}

	/**
	 * Function: sortNatCaseInsByValue
	 *
	 * Returns:
	 * array
	 *
	 * @extern true
	 */
	public static function sortNatCaseInsByValue($array, $value)
	{
		Utility::$sort_key = $value;
		usort($array, 'Utility::compareNatCaseInsSortValues');

		return $array;
	}

	/**
	 * Compare sort values natural and case ins
	 *
	 * @extern true
	 */
	public static function compareNatCaseInsSortValues($a, $b)
	{
		 return strnatcasecmp( $a[Utility::$sort_key], $b[Utility::$sort_key]);
	}

	public static $start_time = null;
    public static $end_time   = null;

    public static function getmicrotime()
    {
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
    }

    public static function startTimer()
    {
		Utility::$start_time = Utility::getmicrotime();
    }

    public static function stopTimer()
    {
		Utility::$end_time = Utility::getmicrotime();
    }

    public static function printTime()
    {
    	Utility::uprint(round((Utility::getmicrotime() - Utility::$start_time), 4));
    }

    public static function timerResult()
    {
        if (is_null(Utility::$start_time))
        {
            return false;
        }
        else if (is_null(Utility::$end_time))
        {
            return false;
        }

        return round((Utility::$end_time - Utility::$start_time), 4);
    }

    public static function encryptData($plaintext)
	{
		if (!ENCRYPTION){return $plaintext;}
	    return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, COA_ENCRYPTION_KEY, $plaintext, 'nofb'));
	}

	public static function decryptData($ciphertext)
	{
		if (!ENCRYPTION){return $ciphertext;}
	    return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, COA_ENCRYPTION_KEY, base64_decode($ciphertext), 'nofb');
	}

    public static function encryptDataAPI($plaintext)
	{
	    return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, COA_ENCRYPTION_KEY, $plaintext, 'nofb'));
	}

	public static function decryptDataAPI($ciphertext)
	{
	    return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, COA_ENCRYPTION_KEY, base64_decode($ciphertext), 'nofb');
	}

	public static function successTrue($data = null, $msg = '') {
		return array(
			'success' => true,
			'msg'     => $msg,
			'data'    => $data
		);
	}

	public static function successFalse($data = null, $msg = '') {
		return array(
			'success' => false,
			'msg'     => $msg,
			'data'    => $data
		);
	}

	public static function data($obj) {
		return $obj['data'];
	}
}
