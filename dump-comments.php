<?php
	if (php_sapi_name() !== "cli") die();

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

        $db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        /* Get all blog IDs */
        $query = "SELECT blog_id, path FROM " . $table_prefix . "blogs";
        $result = $db->query( $query );
        while( $row = $result->fetch_object() ) {
                $blogs[] = $row->blog_id;
                $query = "SELECT * FROM wp_" . $row->blog_id . "_commentmeta";
                $comment_result = $db->query( $query );
                if(!$comment_result) continue;
                echo( "\n### $row->path ###\n" );
                while( $comment = $comment_result->fetch_object() ) {
                        var_dump($comment);
                }
        }
?>

