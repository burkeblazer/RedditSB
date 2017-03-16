<?php
$config_dir = dirname(__FILE__);
$root_dir   = realpath($config_dir.'/..');
$lib_dir    = realpath($root_dir.'/lib/php5');

define('SITE_INSTANCE',       realpath($root_dir));
define('CONFIG_PATH',         $config_dir);
define('LIB_PATH',            $lib_dir);
define('BIN_PATH',            $root_dir.'/bin/');
define('CLASS_PATH',          LIB_PATH);
define('DB_PORT',             5432);
define('DB_USER',             'postgres');
define('DB_HOST',             'localhost');
define('DB_NAME',             'redditsb_dev');
define('DB_PASS',             trim(file_get_contents('/etc/ni/pgsql.txt')));
define('REDDIT_CLIENT_ID',    trim(file_get_contents('/etc/ni/r_client_id.txt')));
define('REDDIT_SECRET_KEY',   trim(file_get_contents('/etc/ni/r_secret_key.txt')));
define('REDDIT_REDIRECT_URL', "http://192.168.1.19/RedditSB/Reddit.php");