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

	abstract class DjangoModel {
		public $pk;
		public $fields;
		public static $pk_counter = 0;

		function __construct($pk = null) {
			if ( $pk == null )
				$this->pk = self::pk_counter;
			else
				$this->pk = $pk;
			self::$pk_counter ++;
			$this->init_fields();
		}

		abstract function init_fields();

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

		function init_fields() {
			$this->fields = array(
				"name"=>null,
				"slug"=>null,
				"status"=>null,
				"administrative_division"=>null,
				"alises"=>null,
				"events_enabled"=>null,
				"chat_enabled"=>null,
				"push_notifications_enabled"=>null,
				"push_notification_channels"=>null,
				"latitude"=>null,
				"longitude"=>null,
				"postal_code"=>null,
				"admin_mail"=>null,
				"created_date"=>null,
				"last_updated"=>null,
				"statistics_enabled"=>null,
				"matomo_url"=>null,
				"matomo_token"=>null,
				"matomo_ssl_verify"=>null,
			);
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

	/* Get all blog IDs */
	$query = "SELECT blog_id FROM " . $table_prefix . "blogs";
	$result = $db->query( $query );
	while ( $row = $result->fetch_object() ) {
		$blogs[] = $row->blog_id;
	}

	$fixtures = new DjangoFixtures();

	/* First update the wp_options and wp_X_options tables */
	foreach ( $blogs as $blog_id ) {
		$region = new Region( $blog_id );
		$region->fields["name"] = "foo";
		$fixtures->append( $region );
	}

	echo($fixtures->dump());
?>
