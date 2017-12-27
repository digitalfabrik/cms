<?php
	define('REPLACE_OLD', "vmkrcmar21.informatik.tu-muenchen.de");
	define('REPLACE_NEW', "integreat");
	require_once("wp-config.php");
	$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

	$query = "SELECT TABLE_NAME FROM wordpress_live.INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE '$table_prefix%_options'";
	$result = $db->query( $query );
	while( $row = $result->mysql_fetch_object() ) {
		$update = "UPDATE " . $row->TABLE_NAME . " SET option_value = REPLACE(url, 'domain1.com/images/', 'domain2.com/otherfolder/')";
	}