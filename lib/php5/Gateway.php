<?php
require_once("DaoInit.php");

class Gateway
{
	protected static $conn = null;

	protected static $prepared_stmts = array();

	/**
	 * Get Method Properties - Returns array of properties for a method, keyed by property name
	 *
	 * @param $method object instance of ReflectionMethod
	 * @return $properties array
	 */
	protected static function getMethodProperties($method)
	{
		$tags = array();
		$comment_block = $method->getDocComment();
		$comment_lines = explode("\n", $comment_block);

		foreach ($comment_lines as $comment_line)
		{
			if (preg_match('/@([a-z]+) {0,}(.*){0,1}/', $comment_line, $results))
			{
				$tag = $results[1];

				$params = trim($results[2]);

				switch ($tag) {
					case 'extern':
						// If extern is specified by itself or explicitly set to true, set it.
						if ($params == '' || preg_match('/true/', strtolower($params)))
						{
							$tags[$tag] = true;
						}

						break;

					case 'admin':
						// If admin is specified by itself or explicitly set to true, set it.
						if ($params == '' || preg_match('/true/', strtolower($params)))
						{
							$tags[$tag] = true;
						}

						break;

					case 'superadmin':
						// If superadmin is specified by itself or explicitly set to true, set it.
						if ($params == '' || preg_match('/true/', strtolower($params)))
						{
							$tags[$tag] = true;
						}

						break;

					case 'perm':
						// Split the rest of the line by space
						$params = explode(" ", $params);

						// Merge perms
						if (is_array($tags[$tag]))
						{
							$tags[$tag] = array_merge($params, $tags[$tag]);
						}
						else
						{
							$tags[$tag] = $params;
						}

						break;

					default:
						$tags[$tag][] = $params;
						break;
				}
			}
		}

		return $tags;
	}

	/**
	 * Validate Method - Checks a method to verify that it can be executed by the current user.
	 *
	 * @param $method object instance of ReflectionMethod
	 */
	protected static function validateMethod($method)
	{
		if (!$method)
		{
			throw new Exception("Unable to find Gateway method: " . $method->getName());
		}

		// Don't allow protected or private methods to be called
		if ($method->isProtected() || $method->isPrivate())
		{
			throw new Exception("Cannot invoke private or protected Gateway method: " . $method->getName());
		}

		// Parse PHPDoc tags for method.
		$doc_tags = self::getMethodProperties($method);

		// Check for extern tag. Only allow function call if @extern is set.
		if (!isset($doc_tags['extern']) || !$doc_tags['extern'])
		{
			throw new Exception('@extern not specified in comment block.  Cannot call method: ' . $method->getName());
		}

		// Check capabilities
		if (isset($doc_tags['admin']) && !User::isAdmin() && !User::isSuperAdmin())
		{
			throw new exception('Administrative rights required for method: ' . $method->getName());
		}

		// Check capabilities
		if (isset($doc_tags['superadmin']) && !User::isSuperAdmin())
		{
			throw new exception('Administrative rights required for method: ' . $method->getName());
		}

		// Check capabilities
		if (isset($doc_tags['perm']) && !User::$current->hasCapability($doc_tags['perm']))
		{
			throw new exception('Current user does not have enough permissions for method: ' . $method->getName());
		}
	}

	/**
	 * Execute Gateway Method - Executes a gateway method with the passed parameters.
	 *
	 * @param $user_id integer ID of current user
	 * @param $method_name string String name of gateway method to execute
	 * @param $method_params array Associative array of potential parameters
	 * @param $instance_id integer
	 * @return $returnData mixed Returns any data that the called method returns.
	 */
	public static function executeMethod($user_id, $method_name, $method_params, $instance_id = 0, $enqueue = false)
	{
		// Parse $method_name to see what gateway class was specified
		if (preg_match('/^([A-Za-z_]+)::([A-Za-z_]+)$/', $method_name, $method_parts))
		{
			$gateway_class = $method_parts[1];
			$method_name   = $method_parts[2];

			// Always use null instance. All methods must be static.
			$gateway_obj = null;
		}
		else
		{
			throw new Exception("Gateway::executeMethod() - Malformed \$method_name = $method_name");
		}

		// Get reflection object for Gateway
		$gateway = new ReflectionClass($gateway_class);

		// Get method information.  The mode must be the name of a function in the gateway class.
		$method = $gateway->getMethod($method_name);

		// Validate method to make sure that it can be called.
		self::validateMethod($method);

		// Get parameters for method
		$params = $method->getParameters();

		// Collect values for each parameter.  Make sure that all required parameters are specified
		$method_args = Array();

		foreach ($params as $param)
		{
			$name = $param->getName();

			if (isset($method_params[$name]))
			{
				$method_args[$param->getPosition()] = $method_params[$name];
			}
			else
			{
				// Value for parameter not found, make sure it's not required
				if (!$param->isOptional())
				{
					throw new Exception("Parameter \"$name\" is required in method \"$method_name\"\n");
				}
				else
				{
					// Manually set default value to avoid screwing up parameters
					$method_args[$param->getPosition()] = $param->getDefaultValue();
				}
			}
		}

		if ($enqueue)
		{
			// Add request to queue
			$result = Queue::enqueue($method_params['scheduled_time'], $method_params['job_name']);

			// Kick off queue process immediately
			Queue::runQueueAsync();
		}
		else
		{
			// Execute method
			$result = $method->invokeArgs($gateway_obj, $method_args);
		}

		// self::getLogger()->log("Return " . print_r($result, true), Zend_Log::DEBUG);


		// Return the result to the controller
		return $result;
	}

	/* Fatal Error Handler - PHP shutdown handler that checks for errors
	 *
	 * This callback sends PHP errors to the gateway log instead of spitting HTML
	 * back inside what should be a JSON response
	 *
	 */
	public static function fatalErrorHandler()
	{
		$isError = false;

		if ($error = error_get_last())
		{
			switch ($error['type'])
			{
				case E_ERROR:
				case E_CORE_ERROR:
				case E_COMPILE_ERROR:
				case E_USER_ERROR:
					$isError = true;
					break;
			}
		}

		if ($isError)
		{
			$logMessage = "FILE ".$error['file'].", LINE ".$error['line'].": ".$error['message'];
			// self::getLogger()->err($logMessage);
		}
	}

	/**
	 * Recursive Delete
	 *
	 * @extern false
	 */
	public static function recursiveDelete($str)
	{
		if (is_file($str))
		{
			return unlink($str);
		}

		if (is_dir($str))
		{
			$scan = glob(rtrim($str,'/').'/*');

			foreach ($scan as $index=>$path)
			{
				self::recursiveDelete($path);
			}

			return rmdir($str);
		}
	}

	/**
	 * Get Sql Connection - Returns a shared Postgres SQL connection.  If none exists then a new one is created
	 *
	 * @return $conn Resource bound to sql connection
	 */
	public static function getSqlConn()
	{
		if (self::$conn && is_resource(self::$conn))
		{
			return self::$conn;
		}

		$conn_str = sprintf('host=%s port=%d dbname=%s user=%s password=%s', DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS);
		self::$conn = pg_connect($conn_str);

		if (!self::$conn)
		{
			throw new Exception('Gateway::getSqlConn() - Could not pg_connect to ' . DB_HOST . ' - ' . DB_NAME);
		}

		return self::$conn;
	}

	/**
	 * Function: closeSqlConn()
	 *
	 * Parameters:
	 * none
	 *
	 * Returns:
	 * null
	 */
	public static function closeSqlConn()
	{
		// Use @ to suppress warning
		@pg_close(self::$conn);
		self::$conn = null;
	}

	/**
	 * Prepare Statement - Creates a new prepared statement in the SQL connection.  This will only prepare a new statement if it hasn't already cached it.
	 *
	 * @param $name string Name of prepared statement
	 * @param $sql string SQL for prepared statement
	 * @return success boonean
	 */
	public static function prepareStmt($name, $sql)
	{
		// Check prepared statement cache so as to not prepare statements twice
		if(isset(self::$prepared_stmts[$name]))
		{
			return true;
		}

		if(!pg_prepare(self::getSqlConn(), $name, $sql))
		{
			throw new Exception("Gateway::prepareStmt() - Unable to prepare statement: $name ($sql)  Reason: " . pg_last_error());
		}

		self::$prepared_stmts[$name] = true;

		return true;
	}
}
