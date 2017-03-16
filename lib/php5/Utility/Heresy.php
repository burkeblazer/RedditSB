<?php

/**
 * Class: Heresy
 * The anti-Doctrine.
 */
class Heresy
{
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
	public static function pgQueryParams($sql, $params)
	{
		$conn = Gateway::getSqlConn();
		$sqh  = pg_query_params($conn, $sql, $params);

		if ($sqh)
		{
			$rows = pg_fetch_all($sqh);

			return ($rows ? $rows : array());
		}
		else
		{
			$error = pg_last_error($conn);
			Utility::throwIfNo(false, '', "Encountered error while executing SQL ($sql): $error");
		}
	}

	/**
	 * Function: select
	 *
	 * Parameters:
	 * $selects
	 * $table
	 * $wheres
	 *
	 * Example:
	 *         SELECT "name" FROM profile   WHERE  profile_id  =  $profile_id
	 * Heresy::select('name',    'profile', array('profile_id' => $profile_id))
	 *
	 * Returns:
	 * $rows
	 *
	 * @extern true
	 */
	public static function select($selects, $table, $wheres = array())
	{
		if (!is_array($selects))
		{
			$selects = array($selects);
		}

		$select_clauses = array();
		$where_clauses  = array();
		$params         = array();

		foreach ($selects as $select_key => $select_value)
		{
			if (is_string($select_key))
			{
				$select_clauses[] = ((strpos($select_key, '(') !== false) ? $select_key : Heresy::quoteWrap($select_key)).' AS '.Heresy::quoteWrap($select_value);
			}
			else
			{
				$select_clauses[] = (strpos($select_value, '(') !== false) ? $select_value : Heresy::quoteWrap($select_value);
			}
		}

		foreach ($wheres as $where_key => $where_value)
		{
			$where_key = Heresy::quoteWrap($where_key);

			if (is_null($where_value))
			{
				$where_clauses[] = "$where_key IS NULL";
			}
			else
			{
				$params[]        = $where_value;
				$where_clauses[] = "$where_key = \$".count($params);
			}
		}

		$where_clauses[] = 'true';

		$rows = Utility::pgQueryParams("
			SELECT
				".implode(",\n\t\t\t\t", $select_clauses)."

			FROM
				".Heresy::quoteWrap($table)."

			WHERE
				".implode(" AND\n\t\t\t\t", $where_clauses).";
		", $params);

		return $rows;
	}

	/**
	 * Function: selectOne
	 *
	 * Parameters:
	 * $selects
	 * $table
	 * $wheres
	 * $indexed (defaults to false)
	 *
	 * Example:
	 *         SELECT    "name" FROM profile   WHERE  profile_id  =  $profile_id LIMIT 1
	 * Heresy::selectOne('name',    'profile', array('profile_id' => $profile_id))
	 *
	 * Returns:
	 * $rows[0]
	 *
	 * @extern true
	 */
	public static function selectOne($selects, $table, $wheres, $indexed = false)
	{
		$rows = Heresy::select($selects, $table, $wheres);

		return ($indexed && $rows[0]) ? array_values($rows[0]) : $rows[0];
	}

	/**
	 * Function: update
	 *
	 * Parameters:
	 * $table
	 * $sets
	 * $wheres
	 * $return_values (defaults to '*')
	 * $indexed (defaults to false)
	 *
	 * Example:
	 *         UPDATE  profile   SET    status  =  false   WHERE  profile_id  =  $profile_id
	 * Heresy::update('profile', array('status' => false), array('profile_id' => $profile_id))
	 *
	 * Returns:
	 * $rows
	 *
	 * @extern true
	 */
	public static function update($table, $sets, $wheres, $return_values = '*', $indexed = false)
	{
		if (!is_array($return_values)) {
			$return_values = array($return_values);
		}

		$set_clauses   = array();
		$where_clauses = array();
		$params        = array();

		foreach ($sets as $set_key => $set_value) {
			if (strpos($set_key, 'heresy_exec_') === 0) {
				$set_key       = Heresy::quoteWrap(str_replace('heresy_exec_', '', $set_key));
				$set_clauses[] = "$set_key = $set_value";
			}
			else {
				$set_key       = Heresy::quoteWrap($set_key);
				$params[]      = is_bool($set_value) ? ($set_value * 1) : $set_value;
				$set_clauses[] = "$set_key = \$".count($params);
			}
		}

		foreach ($wheres as $where_key => $where_value) {
			$where_key = Heresy::quoteWrap($where_key);

			if (is_null($where_value)) {
				$where_clauses[] = "$where_key IS NULL";
			}
			else {
				$params[]        = $where_value;
				$where_clauses[] = "$where_key = \$".count($params);
			}
		}

		$rows = Utility::pgQueryParams("
			UPDATE
				".Heresy::quoteWrap($table)."

			SET
				".implode(",\n\t\t\t\t", $set_clauses)."

			WHERE
				".implode(" AND\n\t\t\t\t", $where_clauses)."

			RETURNING
				".implode(",\n\t\t\t\t", Heresy::quoteWrap($return_values)).";
		", $params);

		return ($indexed && $rows[0]) ? array_values($rows[0]) : $rows[0];
	}

	/**
	 * Function: insertInto
	 *
	 * Parameters:
	 * $table
	 * $values
	 * $return_values (defaults to '*')
	 * $indexed (defaults to false)
	 *
	 * Example:
	 *         INSERT INTO profile ("name", profile_type_id) VALUES ($name, $profile_type_id)
	 * Heresy::insertInto('profile', array('name' => $name, 'profile_type_id' => $profile_type_id))
	 *
	 * Returns:
	 * $row
	 *
	 * @extern true
	 */
	public static function insertInto($table, $values, $return_values = '*', $indexed = false)
	{
		if (!is_array($return_values))
		{
			$return_values = array($return_values);
		}

		$fields     = array_keys($values);
		$param_vars = array();
		$params     = array_values($values);

		for ($i = 1; $i <= count($params); $i++)
		{
			$param_vars[] = "\$$i";
		}

		$rows = Utility::pgQueryParams("
			INSERT INTO
				".Heresy::quoteWrap($table)." (".implode(', ', Heresy::quoteWrap($fields)).")

			VALUES
				(
					".implode(",\n\t\t\t\t\t", $param_vars)."
				)

			RETURNING
				".implode(",\n\t\t\t\t", Heresy::quoteWrap($return_values)).";
		", $params);

		return ($indexed && $rows[0]) ? array_values($rows[0]) : $rows[0];
	}

	/**
	 * Function: quoteWrap
	 *
	 * Parameters:
	 * $keys or $key
	 *
	 * Returns:
	 * $quoted_keys or $quoted_key
	 */
	protected static function quoteWrap($keys)
	{
		Utility::throwIfNo(is_string($keys) || is_array($keys), '', 'Invalid type for $keys.');

		if (is_array($keys))
		{
			$quoted_keys = array();

			foreach ($keys as $key)
			{
				$quoted_keys[] = Heresy::quoteWrap($key);
			}

			return $quoted_keys;
		}
		else if (is_string($keys))
		{
			if ($keys == '*') {return $keys;}

			return '"'.trim($keys, '"').'"';
		}
	}
}
