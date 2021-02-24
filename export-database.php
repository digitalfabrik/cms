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
	define('REPL_DOMAIN_OLD', "cms.integreat-app.de");
	define('REPL_DOMAIN_NEW', "cms-dev.integreat-app.de");
	define('REPL_PATH_OLD', "/");
	define('REPL_PATH_NEW', "/");
	$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

	function now() {
		$objDateTime = new DateTime('NOW');
		return $objDateTime->format('c');
	}

	abstract class DjangoModel {
		public $pk;
		public $fields = array();
		public static $pk_counter = 0;

		function __construct( $pk = null ) {
			if ( $pk == null )
				$this->pk = self::$pk_counter;
			else
				$this->pk = $pk;
			self::$pk_counter ++;
		}

		function is_valid() {
			foreach ( $fields as $item ) {
				if ( $item == null )
					return false;
			}
			return true;
		}
	}

	class Region extends DjangoModel {
		public $model = "cms.region";

		function __construct( $blog ) {
			parent::__construct( $blog->blog_id );
			$this->init_fields( $blog );
		}

		function init_fields( $blog ) {
			$this->fields["name"] = $blog->get_blog_option( "blogname" );
			$this->fields["slug"] = str_replace( "/", "", $blog->path );
			$this->fields["aliases"] = $blog->get_integreat_setting( "aliases" );
			$this->fields["status"] = ( $blog->get_integreat_setting( "hidden" ) == 1 ? "HIDDEN" : ( $blog->get_integreat_setting( "active" ) == 1 ? "ACTIVE" : "ARCHIVED" ) );
			$this->fields["latitude"] = $blog->get_integreat_setting( "latitude" );
			$this->fields["longitude"] = $blog->get_integreat_setting( "longitude" );
			$this->fields["postal_code"] = $blog->get_integreat_setting( "plz" );
			$this->fields["administrative_division"] = ( $blog->get_integreat_setting( "prefix" ) == "Landkreis" ? "RURAL_DISTRICT" : "MUNICIPALITY" );
			$this->fields["events_enabled"] = true;
			$this->fields["chat_enabled"] = true;
			$this->fields["push_notifications_enabled"] = ( $blog->get_integreat_setting( "push_notifications" ) == 1 ? true : false );
			$this->fields["push_notification_channels"] = array("news");
			$this->fields["admin_mail"] = "info@integreat-app.de";
			$this->fields["created_date"] = now();
			$this->fields["last_updated"] = now();
			$this->fields["statistics_enabled"] = ( strpos( $blog->get_blog_option( "active_plugins" ), "wp-piwik" ) ? true : false );
			$this->fields["matomo_url"] = $blog->get_blog_option( "wp-piwik_global-piwik_url" );
			$this->fields["matomo_token"] = $blog->get_blog_option( "wp-piwik_global-piwik_token" );
			$this->fields["matomo_ssl_verify"] = true;
		}
	}

	class Language extends DjangoModel {
		public $model = "cms.language";

		function init_fields() {
			$this->fields = array(
				"code"=>null,
				"english_name"=>null,
				"native_name"=>null,
				"text_direction"=>null,
				"table_of_contents"=>null,
				"created_date"=>null,
				"last_updated"=>null,
			);
		}
	}

	class LanguageTreeNode {
		public $model = "cms.languagetreenode";

		function init_fields() {
			$this->fields = array(
				"language"=>null,
				"parent"=>null,
				"region"=>null,
				"active"=>null,
				"created_date"=>null,
				"last_udpated"=>null,
				"lft"=>null,
				"rght"=>null,
				"tree_id"=>null,
				"level"=>null,
			);
		}
	}

	class Page {
		public $model = "cms.page";

		function init_fields() {
			$this->fields = array(
				"parent"=>null,
				"icon"=>null,
				"region"=>null,
				"explicitly_archived"=>null,
				"mirrored_page"=>null,
				"created_date"=>null,
				"last_updated"=>null,
				"lft"=>null,
				"rght"=>null,
				"tree_id"=>null, // should be the same as the region ID
				"level"=>null,
				"editors"=>null,
				"publishers"=>null,
			);
		}
	}

	class PageTranslation {
		public $model = "cms.pagetranslation";

		function init_fields() {
			$this->fields = array(
				"page"=>null,
				"slug"=>null,
				"title"=>null,
				"status"=>null,
				"text"=>null,
				"language"=>null,
				"currently_in_translation"=>null,
				"version"=>null,
				"minor_edit"=>null,
				"creator"=>null,
				"created_date"=>null,
				"last_updated"=>null,
			);
		}
	}

	class DjangoFixtures {

		function __construct() {
			$this->object_list = array();
		}

		function append( $object ) {
			array_push( $this->object_list, $object );
		}

		function is_valid() {
			foreach ( $this->object_list as $object ) {
				if ( $object->is_valid == false ) {
					return false;
				}
				return true;
			}
		}

		function dump() {
			return json_encode( $this->object_list, JSON_PRETTY_PRINT );
		}
	}

	class WPBlog {
		function __construct( $db, $blog_id, $path ) {
			$this->db = $db;
			$this->blog_id = $blog_id;
			$this->path = $path;
			if ( $blog_id == 1 ) {
				$this->dbprefix = "wp_";
			} else {
				$this->dbprefix = "wp_".$blog_id."_";
			}
		}

		function get_blog_option( $option_name ) {
			$query = "SELECT option_value FROM " . $this->dbprefix . "options WHERE option_name='" . $option_name . "'";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				return utf8_encode($row->option_value);
			}
		}

		function get_integreat_setting( $alias ) {
			$query = "SELECT value FROM " . $this->dbprefix . "ig_settings_config c LEFT JOIN wp_ig_settings s ON c.setting_id=s.id WHERE alias='$alias'";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				return utf8_encode($row->value);
			}
		}

		function get_postmeta( $post_id, $meta_key ) {
			$query = "SELECT meta_value FROM " . $this->dbprefix . "postmeta WHERE post_id=$post_id AND meta_key='" . $meta_key . "'";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				return $row->option_value;
			}
		}
	}

	/* Get all blog IDs */
	$query = "SELECT blog_id, path FROM " . $table_prefix . "blogs";
	$result = $db->query( $query );
	while ( $row = $result->fetch_object() ) {
		$blogs[] = new WPBlog( $db, $row->blog_id, $row->path );
	}
	$fixtures = new DjangoFixtures();

	foreach ( $blogs as $blog ) {
		$region = new Region( $blog );
		$fixtures->append( $region );
	}

	echo($fixtures->dump());
?>
