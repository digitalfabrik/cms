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

	define('REPL_DOMAIN_OLD', "cms.integreat-app.de");
	define('REPL_DOMAIN_NEW', "cms-dev.integreat-app.de");
	define('REPL_PATH_OLD', "/");
	define('REPL_PATH_NEW', "/");
	$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

	function now() {
		$objDateTime = new DateTime('NOW');
		return $objDateTime->format('c');
	}

	class MPTT {
		function __construct( $pk_offset = null ) {
			$this->tree = array();
			if ( $pk_offset ) {
				$this->pk_counter = $pk_offset;
			} else {
				$this->pk_counter = 0;
			}
		}

		function add_node( $node_id, $parent_node_id = null ) {
			if ( sizeof( $this->tree ) == 0 && $parent_node_id == null ) {
				$this->tree[]	= array( "id" => $node_id, "parent" => null, "parent_pk" => null, "left" => 1, "right" => 2, "level" => 0, "pk" => $this->pk_counter );
			} elseif ( sizeof( $this->tree) > 0 && $parent_node_id ) {
				$parent = $this->get_parent( $parent_node_id );
				$this->increase_counts( $parent["right"] );
				$this->tree[] = array( "id" => $node_id, "parent" => $parent_node_id, "parent_pk" => $parent["pk"], "left" => $parent["right"], "right" => ( $parent["right"] + 1 ), "level" => $parent["level"] + 1, "pk" => $this->pk_counter );
			} else {
				return false;
			}
			$this->pk_counter++;
			return true;
		}

		function get_parent( $parent_node_id ) {
			for ( $i = 0; $i < sizeof( $this->tree ); $i++ ) {
				if ( $this->tree[$i]["id"] == $parent_node_id ) {
					return $this->tree[$i];
				}
			}
		}

		function increase_counts( $num ) {
			for ( $i = 0; $i < sizeof( $this->tree ); $i++ ) {
				if ( $this->tree[$i]["left"] >= $num ) {
					$this->tree[$i]["left"] = $this->tree[$i]["left"] + 2;
				}
				if ( $this->tree[$i]["right"] >= $num ) {
					$this->tree[$i]["right"] = $this->tree[$i]["right"] + 2;
				}
			}
		}

		function get_node( $node_id ) {
			foreach ( $this->tree as $key => $node ) {
				if ( $node["id"] == $node_id ) {
					return $node;
				}
			}
		}
  }

	abstract class DjangoModel {
		public $pk;
		public $fields = array();
		public static $pk_counter = array();

		function __construct( $pk = null ) {
			if ( !array_key_exists( $this->model, self::$pk_counter ) )
				self::$pk_counter[$this->model] = 1;
			if ( $pk == null )
				$this->pk = self::$pk_counter[$this->model];
			else
				$this->pk = $pk;
			self::$pk_counter[$this->model] ++;
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
			$this->fields["slug"] = ( $blog->get_blog_option( "blogname") == "Integreat" ? "integreat" : str_replace( "/", "", $blog->path ) );
			$this->fields["aliases"] = ( $blog->get_integreat_setting( "aliases" ) != null ? $blog->get_integreat_setting( "aliases" ) : array() );
			$this->fields["status"] = ( $blog->get_integreat_setting( "disabled" ) == 1 ? "ARCHIVED" : ( $blog->get_integreat_setting( "hidden" ) == 0 ? "ACTIVE" : "HIDDEN" ) );
			$this->fields["latitude"] = $blog->get_integreat_setting( "latitude" );
			$this->fields["longitude"] = $blog->get_integreat_setting( "longitude" );
			$this->fields["postal_code"] = ( $blog->get_integreat_setting( "plz" ) != null ? $blog->get_integreat_setting( "plz" ) : 1);
			$this->fields["administrative_division"] = ( $blog->get_integreat_setting( "prefix" ) == "Landkreis" ? "RURAL_DISTRICT" : "MUNICIPALITY" );
			$this->fields["events_enabled"] = true;
			$this->fields["chat_enabled"] = true;
			$this->fields["push_notifications_enabled"] = ( $blog->get_integreat_setting( "push_notifications" ) == 1 ? true : false );
			$this->fields["push_notification_channels"] = array("news");
			$this->fields["admin_mail"] = "info@integreat-app.de";
			$this->fields["created_date"] = now();
			$this->fields["last_updated"] = now();
			$this->fields["statistics_enabled"] = ( strpos( $blog->get_blog_option( "active_plugins" ), "wp-piwik" ) ? true : false );
			$this->fields["matomo_url"] = ( $blog->get_blog_option( "wp-piwik_global-piwik_url" ) != null ? $blog->get_blog_option( "wp-piwik_global-piwik_url" ) : "" );
			$this->fields["matomo_token"] = ( $blog->get_blog_option( "wp-piwik_global-piwik_token" ) != null ? $blog->get_blog_option( "wp-piwik_global-piwik_token" ) : "");
			$this->fields["matomo_ssl_verify"] = true;
		}
	}

	class Language extends DjangoModel {
		public $model = "cms.language";

		function __construct( $language ) {
			parent::__construct();
			$this->init_fields( $language );
		}

		function init_fields( $language ) {
			$this->fields = array(
				"slug"=>utf8_encode( $language["code"] ),
				"bcp47_tag"=>( $language["tag"] == "uz-uz" && $language["code"] == "ur" ? "ur-ur" : utf8_encode($language["tag"]) ),
				"english_name"=>utf8_encode( $language["english_name"] ),
				"native_name"=>utf8_encode( $language["native_name"] ),
				"text_direction"=>(in_array($language["code"], array('ar','fa','ckb')) ? "RIGHT_TO_LEFT" : "LEFT_TO_RIGHT"),
				"table_of_contents"=>"Inhaltsverzeichnis",
				"created_date"=>now(),
				"last_updated"=>now(),
			);
		}
	}

	class LanguageTreeNode extends DjangoModel {
		public $model = "cms.languagetreenode";

		function __construct( $blog, $language, $active, $mptt_node ) {
			$this->pk = $mptt_node["pk"];
			$this->init_fields( $blog, $language, $active, $mptt_node );
		}

		function init_fields( $blog, $language, $active, $mptt_node ) {
			$this->fields = array(
				"language"=>$language->pk,
				"parent"=>$mptt_node["parent_pk"],
				"region"=>$blog->blog_id,
				"active"=>$active,
				"created_date"=>now(),
				"last_updated"=>now(),
				"lft"=>$mptt_node["left"],
				"rght"=>$mptt_node["right"],
				"tree_id"=>$blog->blog_id,
				"level"=>$mptt_node["level"],
			);
		}
	}

	class Page {
		public $model = "cms.page";

		function __construct( $blog, $post, $mptt_node ) {
			$this->pk = $mptt_node["pk"];
			$this->init_fields( $blog, $post, $mptt_node );
		}

		function init_fields() {
			$this->fields = array(
				"parent"=>$mptt_node["parent_pk"],
				"icon"=>null,
				"region"=>null,
				"explicitly_archived"=>null,
				"mirrored_page"=>null,
				"created_date"=>null,
				"last_updated"=>null,
				"lft"=>$mptt_node["left"],
				"rght"=>$mptt_node["right"],
				"tree_id"=>$blog->blog_id, // should be the same as the region ID
				"level"=>$mptt_node["level"],
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

		function get_language_by_slug( $code ) {
			foreach ( $this->object_list as $object ) {
				if ( $object->model == "cms.language"  &&  $object->fields["slug"] == $code ) {
					return $object;
				}
			}
			return null;
		}

		function get_languagetreenode_by_language_pk( $blog_id, $pk ) {
			foreach ( $this->object_list as $object ) {
				if ( $object->model == "cms.languagetreenode"  && $object->pk == $pk && $object->fields["tree_id"] == $blog_id ) {
					return $object;
				}
			}
			return null;
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

		function get_languages() {
			$query = "SELECT code, english_name, name AS native_name, tag FROM " . $this->dbprefix ."icl_languages l LEFT JOIN " . $this->dbprefix . "icl_languages_translations t ON l.code=t.language_code WHERE t.display_language_code=l.code";
			$result = $this->db->query( $query );

			while ( $row = $result->fetch_object() ) {
				$languages[$row->code] = array( "code"=>$row->code, "english_name"=>$row->english_name, "native_name"=>$row->native_name, "tag"=>$row->tag );
			}
			return $languages;
		}

		function get_used_languages() {
			$query = "SELECT m.code, active FROM " . $this->dbprefix ."icl_locale_map m LEFT JOIN " . $this->dbprefix ."icl_languages l ON m.code=l.code";
			$result = $this->db->query( $query );
			$languages_active = array();
			while ( $row = $result->fetch_object() ) {
				$languages_active[$row->code] = $row->active;
			}
			return $languages_active;
		}

		function get_default_language() {
			$query = "SELECT option_value FROM " . $this->dbprefix ."options WHERE option_name='icl_sitepress_settings'";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				$data = preg_replace_callback( '!s:(\d+):"(.*?)";!', function($m) {
					return 's:'.strlen($m[2]).':"'.$m[2].'";';
				}, $row->option_value );
				$var = unserialize( $data );
				if ( array_key_exists( "default_language", $var ) ) {
					return $var["default_language"];
				} else {
					return "de";
				}
			}
			return null;
		}

		function generate_language_tree( $treenode_pk_counter ) {
			$language_tree = new MPTT( $treenode_pk_counter );

			$used_languages = $this->get_used_languages();
			$default_language = $this->get_default_language();
			$language_tree->add_node(  $default_language );
			unset( $used_languages[$default_language] );
			foreach ( $used_languages as $lang_code => $active ) {
				$language_tree->add_node( $lang_code, $default_language );
			}
			return $language_tree;

		}

		function get_pages_for_language( $language_code ) {
			$query = "SELECT p.ID, p.post_parent FROM " . $this->dbprefix . "posts p LEFT JOIN (SELECT * FROM " . $this->dbprefix . "icl_translations WHERE element_type='post_page') t ON t.element_id=p.ID WHERE t.language_code='$language_code'" ;
			$result = $this->db->query( $query );
			$posts = [];
			while ( $row = $result->fetch_object() ) {
				$posts[] = ["id" => $row->ID, "parent" => ( $row->post_parent == 0 ? null : $row->post_parent ) ];
			}
			return $posts;
		}

		function generate_page_tree( $posts, $pagetree_pk_counter ) {
			$page_tree = new MPTT( $pagetree_pk_counter );
			foreach ( $posts as $post ) {
				$page_tree->add_node( $post["id"], $post["parent"] );
			}
			return $page_tree;
		}

		/* Create array of all revisions in all translations */
		function get_page_translations( $page_id ) {
			
		
		}
	}

	/* Get all blog IDs */
	$query = "SELECT blog_id, path FROM " . $table_prefix . "blogs";
	$result = $db->query( $query );
	while ( $row = $result->fetch_object() ) {
		$blogs[] = new WPBlog( $db, $row->blog_id, $row->path );
	}
	$fixtures = new DjangoFixtures();
	$languages = array();
	$treenode_pk_counter = 1;

	foreach ( $blogs as $blog ) {
		$region = new Region( $blog );
		$fixtures->append( $region );

		/* get available languages */
		foreach ( $blog->get_languages() as $blog_language ) {
			if( !array_key_exists( $blog_language["code"], $languages ) ) {
				$fixtures->append( new Language( $blog_language ) );
				$languages[$blog_language["code"]] = $blog_language;
			}
		}

		/* get used languages and create tree nodes */
		$language_tree = $blog->generate_language_tree( $treenode_pk_counter );
		$treenode_pk_counter = $language_tree->pk_counter;

		foreach ( $blog->get_used_languages() as $used_language => $active) {
			$mptt_node = $language_tree->get_node( $used_language );
			$lang = $fixtures->get_language_by_slug($used_language);
			if ( ! $lang ) { fwrite(STDERR, "Blog " . $blog->blog_id . ": Skipping language $used_language.\n"); continue; }
			$tree_node = new LanguageTreeNode( $blog, $lang, $active, $mptt_node );
			$fixtures->append( $tree_node );
		}
		
		/* get pages in main language and create tree */
		$posts = $this->get_pages_for_language( $this->get_default_language() );
		$page_tree = generate_page_tree( $posts, $pagetree_pk_counter );
		$pagetree_pk_counter = $page_tree->pk_counter;
		foreach ( $posts as $post ) {
			$mptt_node = $page_tree->get_node($post["id"]);
			$page_tree_node = new Page( $blog, null, $mptt_node );
			$fixtures->append( $page_tree_node );
			//foreach ( $blog->get_page_translations() as $translation ) {
			//	$page_translation = new PageTranslation( $translation );
			//	$fixtures->append( $page_translation );
			//}

		}
	
		if ( $blog->blog_id >= 15 ) { break; }
	}

	echo($fixtures->dump());
?>
