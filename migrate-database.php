<?php
	$debug = false;

	$configuration = parse_ini_file("config.ini", true);

	// ** MySQL settings - You can get this info from your web host ** //
	$db_configuration = $configuration['database'];
	/** The name of the database for WordPress */
	define('DB_NAME', $db_configuration['name']);

	/** MySQL database username */
	define('DB_USER', $db_configuration['user']);

	/** MySQL database password */
	define('DB_PASSWORD', $db_configuration['password']);

	/** MySQL hostname */
	define('DB_HOST', $db_configuration['host']);

	/** Database Charset to use in creating database tables. */
	define('DB_CHARSET', $db_configuration['charset']);

	/** The Database Collate type. Don't change this if in doubt. */
	define('DB_COLLATE', $db_configuration['collate']);

	$table_prefix = 'wp_';

	echo "starting migration";
	define('REPL_DOMAIN_OLD', "web.");
	define('REPL_DOMAIN_NEW', "cms.");
	define('REPL_PATH_OLD', "integreat-app");
	define('REPL_PATH_NEW', "integreat-app");
	$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

	/* Get all blog IDs */
	$query = "SELECT blog_id FROM " . $table_prefix . "blogs";
	$result = $db->query( $query );
	while( $row = $result->fetch_object() ) {
		$blogs[] = $row->blog_id;
	}
	//var_dump($blogs);
	/* First update the wp_options and wp_X_options tables */
	/*foreach( $blogs as $blog_id ) {
		$update = "UPDATE " . $table_prefix . $blog_id . "_options SET option_value = REPLACE(option_value, '" . REPL_DOMAIN_OLD . REPL_PATH_OLD . "', '" . REPL_DOMAIN_NEW . REPL_PATH_NEW . "') WHERE option_name = 'siteurl' OR option_name = 'home'";
		if($debug)
			var_dump( $update );
		else {
			var_dump( $update );
			$db->query( $update );
		}
	}
*/
/*
	$update = "UPDATE " . $table_prefix . "options SET option_value = REPLACE(option_value, '" . REPL_DOMAIN_OLD . REPL_PATH_OLD . "', '" . REPL_DOMAIN_NEW . REPL_PATH_NEW . "') WHERE option_name = 'siteurl' OR option_name = 'home'";
	if($debug)
		var_dump( $update );
	else {
		var_dump( $update );
		$db->query( $update );
	}

	/* Update the wp_blogs 
	$update = "UPDATE " . $table_prefix . "blogs SET domain = REPLACE(domain, '" . REPL_DOMAIN_OLD . "', '" . REPL_DOMAIN_NEW . "')";
	if($debug)
		var_dump( $update );
	else {
		var_dump( $update );
		$db->query( $update );
	}

	$update = "UPDATE " . $table_prefix . "blogs SET path = REPLACE(path, '" . REPL_PATH_OLD . "', '" . REPL_PATH_NEW . "')";
	if($debug)
		var_dump( $update );
	else {
		var_dump( $update );
		$db->query( $update );
	}
	*/
	
	/* update the wp_posts */
	foreach( $blogs as $blog_id ) {
		$update = "UPDATE " . $table_prefix . $blog_id . "_posts SET post_content = REPLACE(post_content, '" . REPL_DOMAIN_OLD . REPL_PATH_OLD . "', '" . REPL_DOMAIN_NEW . REPL_PATH_NEW . "')";
		if($debug)
			var_dump($update);
		else {
			var_dump( $update );
			$db->query( $update );
		}
	}
?>