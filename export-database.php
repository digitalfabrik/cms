<?php

	if (php_sapi_name() != 'cli') {
		die("Only cli execution is allowed.");
	}

	$debug = false;

	require_once("wp-includes/formatting.php");

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

	$slug_map_file = tempnam('tmp', 'integreat-export-');
	fwrite(STDERR, "Slug Mapping File: ". $slug_map_file . "\n");
	$slug_map_file_resource = fopen($slug_map_file, 'w');
	fputs($slug_map_file_resource, "Region Slug;Page ID;Translation ID;Language ID;Version;WP Slug\n");

	function now() {
		$objDateTime = new DateTime('NOW');
		return $objDateTime->format('c');
	}

	class MPTT {
		function __construct( $pk_offset = null, $id = null ) {
			$this->id = $id;
			$this->tree = array();
			if ( $pk_offset ) {
				$this->pk_counter = $pk_offset;
			} else {
				$this->pk_counter = 0;
			}
		}

		function add_node( $node_id, $parent_node_id = null ) {
			if ( sizeof( $this->tree ) == 0 && $parent_node_id == null ) {
				$this->tree[]	= array( "id" => $node_id, "parent" => null, "parent_pk" => null, "left" => 1, "right" => 2, "level" => 1, "pk" => $this->pk_counter );
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

	class User extends DjangoModel {
		public $model = "cms.user";

		function __construct( $user, $blog_roles ) {
			parent::__construct( (int)$user->ID );
			$this->init_fields( $user, $blog_roles );
		}

		function init_fields( $user, $blog_roles ) {
			$group = $this->select_role( $this->combine_roles( $blog_roles ));
			$regions = $this->filter_regions( array_keys( $blog_roles ) );
			$this->fields["username"] = $user->user_login;
			$this->fields["password"] = "bcrypt_php$" . $user->user_pass;
			$this->fields["email"] = $user->user_email;
			$this->fields["first_name"] = "";
			$this->fields["last_name"] = "";
			$this->fields["is_superuser"] = False;
			$this->fields["is_staff"] = False;
			$this->fields["is_active"] = (sizeof($regions) == 0 ? False : True);
			$this->fields["groups"] = ( is_null( $group ) ? array(2) : array( $group ) );
			$this->fields["regions"] = $regions;
			$this->fields["expert_mode"] = ( $group === 1 ? true : false );  // turn on if Verwalter
		}

		static function filter_regions( $regions ) {
			global $existing_regions;
			$result = [];
			foreach ( $regions as $region ) {
				if ( $region === 0 || !in_array( $region, $existing_regions ) ) {
					// pass
				} else {
					$result[] = $region;
				}
			}
			return $result;
		}

		static function combine_roles( $blog_roles ) {
			$all_roles = array();
			foreach ( $blog_roles as $blog => $roles ) {
				$all_roles = array_merge( $all_roles, $roles );
			}
			return $all_roles;
		}

		static function select_role( $roles ) {
			if ( array_key_exists( "manager", $roles )) {
				return 1; // -> Role MANAGEMENT
			} elseif ( array_key_exists( "trustworthy_organization", $roles )) {
				return 2; // -> Role EDITOR
			} elseif ( array_key_exists( "event_planer", $roles )) {
				return 3; // -> Role EVENT_MANAGER
			}
			return null;
		}
	}

	class Region extends DjangoModel {
		public $model = "cms.region";

		function __construct( $blog ) {
			parent::__construct( (int)$blog->blog_id );
			$this->init_fields( $blog );
		}

		function init_fields( $blog ) {
			$this->fields["name"] = ( $blog->get_integreat_setting( "name_without_prefix" ) ? $blog->get_integreat_setting( "name_without_prefix" ) : $blog->get_blog_option( "blogname" ) );
			$this->fields["slug"] = ( $blog->slug );
			$this->fields["aliases"] = ( $blog->get_integreat_setting( "aliases" ) != null ? $blog->get_integreat_setting( "aliases" ) : array() );
			$this->fields["status"] = ( $blog->get_integreat_setting( "disabled" ) == 1 ? "ARCHIVED" : ( $blog->get_integreat_setting( "hidden" ) == 0 ? "ACTIVE" : "HIDDEN" ) );
			$this->fields["latitude"] = $blog->get_integreat_setting( "latitude" );
			$this->fields["longitude"] = $blog->get_integreat_setting( "longitude" );
			$this->fields["postal_code"] = ( $blog->get_integreat_setting( "plz" ) != null ? $blog->get_integreat_setting( "plz" ) : 1);
			$this->fields["administrative_division"] = $this->get_administrative_division( $blog->get_blog_option( "blogname" ), $blog->get_integreat_setting( "prefix" ) );
			$this->fields["administrative_division_included"] = ( $blog->get_integreat_setting( "prefix" ) !== "" );
			$this->fields["events_enabled"] = true;
			$this->fields["chat_enabled"] = true;
			$this->fields["push_notifications_enabled"] = ( $blog->get_integreat_setting( "push_notifications" ) == 1 ? true : false );
			$this->fields["admin_mail"] = "info@integreat-app.de";
			$this->fields["created_date"] = now();
			$this->fields["last_updated"] = now();
			$this->fields["statistics_enabled"] = ( strpos( $blog->get_blog_option( "active_plugins" ), "wp-piwik" ) ? true : false );
			$this->fields["matomo_token"] = ( $blog->get_blog_option( "wp-piwik_global-piwik_token" ) != null ? $blog->get_blog_option( "wp-piwik_global-piwik_token" ) : "");
			$this->fields["matomo_id"] = ( in_array( $blog->get_blog_option( "wp-piwik-site_id" ) , ["n/a", null, "", "0"] ) ? null : $blog->get_blog_option( "wp-piwik-site_id" ) );
		}

		function get_administrative_division( $name, $prefix ) {
			switch ($prefix) {
				case "Landkreis":
					$administrative_division = "RURAL_DISTRICT";
					break;
				case "Kreis":
					$administrative_division = "DISTRICT";
					break;
				case "Stadt":
					$administrative_division = "CITY";
					break;
				case "Stadt und Landkreis":
					$administrative_division = "CITY_AND_DISTRICT";
					break;
				case "Region":
					$administrative_division = "REGION";
					break;
				default:
					if (stripos($name, "kreis") !== false) {
						$administrative_division = "RURAL_DISTRICT";
					} else {
						$administrative_division = "MUNICIPALITY";
					}
			}
			return $administrative_division;
		}

	}

	class Language extends DjangoModel {
		public $model = "cms.language";

		function __construct( $language ) {
			parent::__construct();
			$this->init_fields( $language );
		}

		function init_fields( $language ) {
			if ( empty($language["tag"]) ) {
				$language["tag"] = $language["code"];
			}
			list($primary_cc, $secondary_cc) = $this->get_country_codes( mb_substr( $language["code"], 0, 2 ) );
			$this->fields = array(
				"slug"=>$language["code"],
				"bcp47_tag"=>( $language["tag"] == "uz-uz" && $language["code"] == "ur" ? "ur-ur" : $language["tag"] ),
				"primary_country_code"=>$primary_cc,
				"secondary_country_code"=>$secondary_cc,
				"english_name"=>$language["english_name"],
				"native_name"=>$language["native_name"],
				"text_direction"=>(in_array($language["code"], array('ar','fa','ckb')) ? "RIGHT_TO_LEFT" : "LEFT_TO_RIGHT"),
				"table_of_contents"=>"Inhaltsverzeichnis",
				"created_date"=>now(),
				"last_updated"=>now(),
			);
		}

		function get_country_codes( $language_code ) {
			$country_code_mapping = [
				"am" => ["et", "er"],
				"ar" => ["dz", "sa"],
				"bs" => ["ba", ""],
				"ca" => ["es", ""],
				"ck" => ["ir", "iq"],
				"cs" => ["cz", ""],
				"cy" => ["gb", ""],
				"da" => ["dk", ""],
				"el" => ["gr", ""],
				"en" => ["gb", "us"],
				"et" => ["ee", ""],
				"eu" => ["es", "fr"],
				"fa" => ["ir", "af"],
				"ga" => ["ie", "gb"],
				"hb" => ["rs", ""],
				"he" => ["il", ""],
				"hi" => ["in", ""],
				"hy" => ["am", ""],
				"ja" => ["jp", ""],
				"ka" => ["ge", ""],
				"km" => ["sy", "tr"],
				"ko" => ["kr", "kp"],
				"ku" => ["sy", "tr"],
				"la" => ["va", ""],
				"mo" => ["md", "ro"],
				"ms" => ["my", ""],
				"nb" => ["no", ""],
				"ne" => ["np", ""],
				"pa" => ["pk", "in"],
				"pe" => ["ir", "af"],
				"pu" => ["af", "ir"],
				"qu" => ["bo", "ec"],
				"sl" => ["si", ""],
				"sq" => ["al", ""],
				"sr" => ["rs", ""],
				"sv" => ["se", ""],
				"ta" => ["in", "lk"],
				"ti" => ["er", "et"],
				"uk" => ["ua", ""],
				"ur" => ["pk", "in"],
				"vi" => ["vn", ""],
				"yi" => ["ba", "ro"],
				"zh" => ["cn", ""],
				"zu" => ["za", "bw"],
			];
			if (array_key_exists( $language_code, $country_code_mapping )) {
				return $country_code_mapping[$language_code];
			} else {
				return [$language_code, ""];
			}
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
				"rgt"=>$mptt_node["right"],
				"tree_id"=>$blog->blog_id,
				"depth"=>$mptt_node["level"],
			);
		}
	}

	class Page extends DjangoModel {
		public $model = "cms.page";

		function __construct( $blog, $mptt_node, $page_tree_counter ) {
			$this->pk = $mptt_node["pk"];
			$this->init_fields( $blog, $mptt_node, $page_tree_counter );
		}

		function init_fields( $blog, $mptt_node, $page_tree_counter ) {
			global $media_pk_map;
			$attachment_guid = $blog->get_post_thumbnail_guid( $mptt_node["id"] );
			$this->fields = array(
				"parent"=>$mptt_node["parent_pk"],
				"icon"=>( $attachment_guid ? $media_pk_map[$blog->blog_id][$attachment_guid] : null),
				"region"=>(int)$blog->blog_id,
				"explicitly_archived"=>$blog->get_trashed_status( $mptt_node["id"] ),
				"mirrored_page"=>null,
				"mirrored_page_first"=>null,
				"created_date"=>now(),
				"lft"=>$mptt_node["left"],
				"rgt"=>$mptt_node["right"],
				"tree_id"=>$page_tree_counter,
				"depth"=>$mptt_node["level"],
				"editors"=>[],
				"publishers"=>[],
			);
		}
	}

	class PageTranslation extends DjangoModel {
		public $model = "cms.pagetranslation";

		function __construct( $translation ) {
			parent::__construct();
			$this->init_fields( $translation );
		}

		function init_fields( $translation ) {
			$this->fields = array(
				"page"=>$translation["page"],
				"slug"=>$translation["slug"],
				"title"=>( empty($translation["title"]) ? "No title" : mb_substr($translation["title"], 0, 250) ),
				"status"=>$translation["status"],
				"content"=>$translation["content"],
				"language"=>$translation["language"],
				"currently_in_translation"=>$translation["currently_in_translation"],
				"version"=>$translation["version"],
				"minor_edit"=>$translation["minor_edit"],
				"creator"=>$translation["creator"],
				//"created_date"=>str_replace("0000-00-00","1970-01-01",$translation["created_date"]),
				"last_updated"=>str_replace("0000-00-00","1970-01-01",$translation["last_updated"]),
			);
		}
  }
	class Imprint extends DjangoModel {
		public $model = "cms.imprintpage";

		function __construct( $blog ) {
			parent::__construct();
			$this->init_fields( $blog );
		}

		function init_fields( $blog ) {
			$this->fields = array(
				"region"=>(int)$blog->blog_id,
				"created_date"=>now(),
			);
		}
	}

	class ImprintTranslation extends DjangoModel {
		public $model = "cms.imprintpagetranslation";

		function __construct( $translation ) {
			parent::__construct();
			$this->init_fields( $translation );
		}

		function init_fields( $translation ) {
			$this->fields = array(
				"page_id"=>$translation["page"],
				"title"=>( empty($translation["title"]) ? "Impressum" : mb_substr($translation["title"], 0, 250) ),
				"status"=>"PUBLIC",
				"content"=>$translation["content"],
				"language_id"=>$translation["language"],
				"currently_in_translation"=>False,
				"version"=>1,
				"minor_edit"=>False,
				"creator_id"=>$translation["creator"],
				"last_updated"=>str_replace("0000-00-00","1970-01-01",$translation["last_updated"]),
			);
		}
	}

	class MediaFile extends DjangoModel {
		public $model = "cms.mediafile";

		function __construct( $blog, $item, $file_path ) {
			parent::__construct();
			$this->init_fields( $blog, $item, $file_path );
		}

		function init_fields( $blog, $item, $file_path) {
			$this->fields = array(
				"file" => $blog->blog_id . "/" . $item->meta_value,
				"thumbnail" => (in_array(strtolower(substr($item->meta_value, -4)), [".svg", ".pdf", "docx", ".doc", ".xls", "xlsx"]) ? null : ($blog->blog_id . "/" . $file_path[0] . "/" . $file_path[1] . "/thumbnail/" . $file_path[2]) ),
				"type" =>$item->post_mime_type,
				"name" =>$file_path[2],
				"parent_directory" => null,
				"region" => $blog->blog_id,
				"alt_text" => $item->post_title,
				"uploaded_date" => $item->post_date_gmt
			);
		}
  }

	class DjangoDirectory extends DjangoModel {
		public $model = "cms.directory";

		function __construct(  ) {
			parent::__construct();
			$this->init_fields(  );
		}

		function init_fields( $translation ) {
			$this->fields = array(
				"name" => "",
				"region" => null,
				"parent" => null,
				"created_date" => ""
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
			return json_encode( $this->object_list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
			fwrite(STDERR, "JSON ERROR: ". json_last_error());
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

			$this->slug = ( $this->get_blog_option( "blogname") == "Integreat" ? "integreat" : str_replace( "/", "", $path ) );
		}

		function get_blog_option( $option_name ) {
			$query = "SELECT option_value FROM " . $this->dbprefix . "options WHERE option_name='" . $option_name . "'";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				return $row->option_value;
			}
		}

		function get_integreat_setting( $alias ) {
			$query = "SELECT value FROM " . $this->dbprefix . "ig_settings_config c LEFT JOIN wp_ig_settings s ON c.setting_id=s.id WHERE alias='$alias'";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				return $row->value;
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
			$query = "SELECT code, active FROM " . $this->dbprefix ."icl_languages WHERE active=1";
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

		function get_pages_for_language( $language_code, $parents ) {
			$query = "SELECT p.ID, p.post_parent FROM " . $this->dbprefix . "posts p LEFT JOIN (SELECT * FROM " . $this->dbprefix . "icl_translations WHERE element_type='post_page') t ON t.element_id=p.ID WHERE t.language_code='$language_code' AND p.post_parent IN (" . implode( ",", $parents ) . ") AND p.post_status!='auto-draft' ORDER BY menu_order ASC" ;
			$result = $this->db->query( $query );
			$posts = [];
			while ( $row = $result->fetch_object() ) {
				$posts[] = ["id" => $row->ID, "parent" => $row->post_parent ];
			}
			return $posts;
		}

		function generate_page_tree( $page_tree_node_pk_counter, $root_page_id, $page_tree_counter ) {
			global $page_pk_map;
			global $page_wp_map;
			global $page_mirror_map;
			/* Get pages in hierarchical order, starting with a root page */
			$new_posts = $this->get_pages_for_language( $this->get_default_language(), [ $root_page_id ] );
			$posts = $new_posts;
			while ( ! empty( $new_posts ) ) {
				$new_posts = $this->get_pages_for_language( $this->get_default_language(), array_column( $new_posts, "id" ));
				$posts = array_merge( $posts, $new_posts );
			}
			$page_tree = new MPTT( $pk_offset = $page_tree_node_pk_counter, $id = $page_tree_counter );
			if ( $page_tree->add_node( $root_page_id, null ) ) {
				$page_pk_map[$this->blog_id][$root_page_id] = $page_tree->pk_counter - 1;
				$page_wp_map[$page_tree->pk_counter - 1] = array( "blog_id" => $this->blog_id, "post_id" => $root_page_id );
				$page_mirror_map[$this->blog_id][$root_page_id] = $this->get_mirrored_page( $root_page_id );
			} else {
				return false;
			}
			foreach ( $posts as $post ) {
				if ( $page_tree->add_node( $post["id"], $post["parent"] ) ) {
					$page_pk_map[$this->blog_id][$post["id"]] = $page_tree->pk_counter - 1;
					$page_wp_map[$page_tree->pk_counter - 1] = array( "blog_id" => $this->blog_id, "post_id" => $post["id"] );
					$page_mirror_map[$this->blog_id][$post["id"]] = $this->get_mirrored_page( $post["id"] );
				}
			}
			return $page_tree;
		}

		/* Get the post ID that is the first version (post parent) of the page in the given language */
		function get_page_language_root_id( $post_id, $language ) {
			$query = "SELECT element_id FROM " . $this->dbprefix . "icl_translations WHERE trid IN (SELECT trid FROM " . $this->dbprefix . "icl_translations WHERE element_type='post_page' AND element_id='" . $post_id . "') AND language_code = '" . $language . "'";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				return $row->element_id;
			}
			return false;
		}

		function get_mirrored_page( $post_id ) {
			$query = "SELECT * FROM " . $this->dbprefix . "postmeta WHERE post_id=" . $post_id . " AND (meta_key='ig-attach-content-position' OR meta_key='ig-attach-content-blog' OR meta_key='ig-attach-content-page') AND meta_value IS NOT Null" ;
			$result = $this->db->query( $query );
			$source_page = array();
			while ( $row = $result->fetch_object() ) {
				if ( $row->meta_key == "ig-attach-content-position" ) {
					$source_page["pos"] = $row->meta_value;
				}
				elseif ( $row->meta_key == "ig-attach-content-blog" ) {
					$source_page["blog_id"] = $row->meta_value;
				}
				elseif ( $row->meta_key == "ig-attach-content-page" ) {
					$source_page["post_id"] = $row->meta_value;
				}
			}
			return $source_page;
		}

		/* Create array of all revisions in all translations */
		function get_page_translations( $mptt_node, $language, $language_pk ) {
			$parent = $this->get_page_language_root_id( $mptt_node["id"], $language );
			if ( !$parent ) {
				return [];
			}
			$query = "SELECT * FROM " . $this->dbprefix . "posts WHERE (post_parent = " . $parent . " AND post_type='revision') OR ID = " . $parent . " ORDER BY ID ASC" ;
			$result = $this->db->query( $query );
			$translations = [];
			$version = 1;
			$status = "DRAFT";
			$slug = null;
			while ( $row = $result->fetch_object() ) {
				if ( is_null($slug) ) $slug = $row->post_name;
				if ( $row->post_status == "auto-draft" || $row->post_status == "draft" )
					$status = "DRAFT";
				elseif ( $row->post_status == "private" || $row->post_status == "trash" )
					$status = "REVIEW";
				elseif ( $row->post_status == "publish" )
					$status = "PUBLIC";
				else
					$status = $status; // inherit

				$page_translation = new PageTranslation([
					"page"=>$mptt_node["pk"],
					"slug"=>str_replace( ["!", "#", "&", "'", "(", ")", "*", "*", "+", ",", "/", ":", ";", "=", "?", "@", "[", "]"], "", urldecode($slug) ),
					"title"=>$row->post_title,
					"status"=>$status,
					"content"=>wpautop($row->post_content),
					"language"=>$language_pk,
					"currently_in_translation"=>false,
					"version"=>$version,
					"minor_edit"=>false,
					"creator"=>$row->post_author,
					"created_date"=>$row->post_date_gmt,
					"last_updated"=>$row->post_modified_gmt,
				]);
				global $slug_map_file_resource;
				fputs($slug_map_file_resource, "$this->slug;$mptt_node[pk];$page_translation->pk;$language_pk;$version;$slug\n");
				$page_translations[] = $page_translation;
				$version++;
			}
			return $page_translations;
		}

		function export_imprint( $language_pk_map ) {
			global $fixtures;
			$imprintpage = new Imprint( $this );
			$fixtures->append( $imprintpage );

			foreach ( $this->get_used_languages() as $used_language => $active) {
				if ( ! array_key_exists( $used_language, $language_pk_map ) ) continue;
				$version = 1;
				$query = "SELECT * FROM " . $this->dbprefix . "posts p LEFT JOIN (SELECT * FROM " . $this->dbprefix . "icl_translations WHERE element_type='post_disclaimer') t ON t.element_id=p.ID WHERE t.language_code='".$used_language."' AND p.post_type='disclaimer' ORDER BY ID ASC";
				$result = $this->db->query( $query );
				while ( $row = $result->fetch_object() ) {
					$fixtures->append( new ImprintTranslation([
						"page"=>$imprintpage->pk,
						"title"=>$row->post_title,
						"status"=>"PUBLIC",
						"content"=>wpautop($row->post_content),
						"language"=>$language_pk_map[$row->language_code],
						"currently_in_translation"=>false,
						"version"=>$version,
						"minor_edit"=>false,
						"creator"=>$row->post_author,
						"last_updated"=>$row->post_modified_gmt,
					]) );
					$version++;
				}
			}
		}


		function export_attached_files( ) {
			global $media_pk_map;
			global $fixtures;
			$query = "SELECT ID,guid,meta_value,post_mime_type,post_date_gmt,post_title FROM " . $this->dbprefix . "posts p LEFT JOIN (SELECT * FROM " . $this->dbprefix . "postmeta WHERE meta_key='_wp_attached_file') AS pm ON p.ID=pm.post_id WHERE post_type='attachment' GROUP BY guid";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				if ( $row->meta_value === null ) { continue; }

				$file_path = explode("/", $row->meta_value);
				if ( count( $file_path ) < 2 ) {
				$file_path[0] = "1970";
					$file_path[1] = "01";
					$file_path[2] = $row->meta_value;
				}

				$full_file_path = '/var/www/cms/wp-content/uploads/sites/' . $this->blog_id . "/" .  $row->meta_value;
				if ( ! file_exists($full_file_path) ) {
					continue;
				}

				$media_file = new MediaFile( $this, $row, $file_path );
				$fixtures->append( $media_file );
				$media_pk_map[$this->blog_id][$row->guid] = $media_file->pk;
			}
		}

		function get_trashed_status( $post_id ) {
			$query = "SELECT post_status FROM " . $this->dbprefix . "posts WHERE ((post_parent=$post_id AND post_type='revision') OR ID=$post_id) AND post_status!='inherit' ORDER BY ID DESC LIMIT 1";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				if ( $row->post_status == "trash" ) {
					return true;
				}
			}
			return false;
		}

		function get_post_thumbnail_guid( $post_id ) {
			// original post ID -> post meta -> meta_key -> post guid
			$thumbnail_post = null;
			$query = "SELECT meta_value FROM " . $this->dbprefix . "postmeta WHERE post_id=$post_id AND meta_key='_thumbnail_id'";
			$result = $this->db->query( $query );
			while ( $row = $result->fetch_object() ) {
				$thumbnail_post = (int)$row->meta_value;
			}
			if ( ! $thumbnail_post ) return null;
			$query = "SELECT guid FROM " . $this->dbprefix . "posts WHERE ID=$thumbnail_post";
			while ( $row = $this->db->query( $query )->fetch_object() ) {
				return $row->guid;
			}
			return null;
		}
	}

	function get_users( $db ) {
		$query = "SELECT * FROM wp_users";
		$users = array();
		$result = $db->query( $query );
		while ( $row = $result->fetch_object() ) {
			$users[] = $row;
		}
		return $users;
	}

	function get_user_blog_roles( $db, $user_id ) {
		$query = "SELECT * FROM wp_usermeta WHERE user_id=$user_id AND meta_key LIKE '%_capabilities';";
		$blogs = array();
		$result = $db->query( $query );
		while ( $row = $result->fetch_object()) {
			$blog_id = (int)str_replace("_capabilities", "", str_replace("wp_", "", $row->meta_key));
			$blogs[$blog_id] = unserialize($row->meta_value);
		}
		return $blogs;
	}

	/* Get all blog IDs */
	$query = "SELECT blog_id, path FROM " . $table_prefix . "blogs";
	$result = $db->query( $query );
	while ( $row = $result->fetch_object() ) {
		$blogs[] = new WPBlog( $db, $row->blog_id, $row->path );
	}
	$fixtures = new DjangoFixtures();
	$languages = array();
	$language_pk_map = array(); // map a language code to the Django primary key
	$lang_tree_node_pk_counter = 1;
	$page_tree_node_pk_counter = 1;
	$page_tree_counter = 1;
	$page_pk_map = array(); // map a blog and post ID to the Django page primary key
	$page_wp_map = array(); // map Django page PKs to WP blog and post IDs
	$page_mirror_map = array(); // Map WP source to target page. source is the content source, target the page were the content is included.
	                            // structure: $page_mirror_map[tgt_blog_id][tgt_post_id] = array("blog_id"=>src_blog_id, "post_id"=>src_post_id, "pos_beg":bool).
	$media_pk_map = array(); // map WP blog and attachment post ID to Django PK

	foreach ( $blogs as $blog ) {
		$region = new Region( $blog );
		fwrite(STDERR, "Exporting blog " . $blog->blog_id . "\n");
		$fixtures->append( $region );

		/* get available languages */
		foreach ( $blog->get_languages() as $blog_language ) {
			if( !array_key_exists( $blog_language["code"], $languages ) ) {
				$lang_fixture = new Language( $blog_language );
				$language_pk_map[$blog_language["code"]] = $lang_fixture->pk;
				$fixtures->append( $lang_fixture );
				$languages[$blog_language["code"]] = $blog_language;
			}
		}

		/* get used languages and create tree nodes */
		$language_tree = $blog->generate_language_tree( $lang_tree_node_pk_counter );
		$lang_tree_node_pk_counter = $language_tree->pk_counter;
		//fwrite(STDERR, "TreeNode PK Counter: " . $lang_tree_node_pk_counter . "\n");

		foreach ( $blog->get_used_languages() as $used_language => $active) {
			$mptt_node = $language_tree->get_node( $used_language );
			$lang = $fixtures->get_language_by_slug($used_language);
			if ( ! $lang ) { fwrite(STDERR, "Blog " . $blog->blog_id . ": Skipping language $used_language.\n"); continue; }
			$tree_node = new LanguageTreeNode( $blog, $lang, $active, $mptt_node );
			$fixtures->append( $tree_node );
		}

		// export images into fixtures and create WordPress
		$blog->export_attached_files();
		//var_dump($media_pk_map);
		/* get level 0 pages and generate a page tree for them */
		$new_posts = $blog->get_pages_for_language( $blog->get_default_language(), [ 0 ] );
		foreach ( $new_posts as $root_post ) {
			$page_tree = $blog->generate_page_tree( $page_tree_node_pk_counter, $root_post["id"], $page_tree_counter );
			$page_tree_node_pk_counter = $page_tree->pk_counter;
			foreach ( $page_tree->tree as $mptt_node ) {
				$page_tree_node = new Page( $blog, $mptt_node, $page_tree_counter );
				$fixtures->append( $page_tree_node );
				foreach ( $blog->get_used_languages() as $used_language => $active) {
					if ( ! array_key_exists( $used_language, $language_pk_map ) ) continue;
					foreach ( $blog->get_page_translations( $mptt_node, $used_language, $language_pk_map[$used_language] ) as $page_translation ) {
						$fixtures->append( $page_translation );
					}
				}
			}
			$page_tree_counter++;
		}
		$blog->export_imprint( $language_pk_map );
		//if ( $blog->blog_id >= 2 ) { break; }
	}

	// after all pages have been exported, loop over all fixtures with type page again and look up mirrored page PK and page icons
	fwrite(STDERR, "Fixing mirrored pages foreign keys.\n");
	foreach ( $fixtures->object_list as $key => $object ) {
		if ( $object->model == "cms.page") {
			$source_page = $page_mirror_map[$page_wp_map[$object->pk]["blog_id"]][$page_wp_map[$object->pk]["post_id"]];
			if ( ! $source_page ) continue;
			$object->fields["mirrored_page"] = $page_pk_map[$source_page["blog_id"]][$source_page["post_id"]];
			$object->fields["mirrored_page_first"] = ( array_key_exists("pos", $source_page) && $source_page["pos"] == "end" ? 0 : 1 );
			$fixtures->object_list[$key] = $object;
		}
	}

	fwrite(STDERR, "Dumping users.\n");
	$existing_regions = array_keys( $page_pk_map );
	$users = get_users( $db );
	$user_id_list = [];
	foreach ( $users as $user ) {
		fwrite(STDERR, "User " . $user->ID . "\n");
		$user_id_list[] = $user->ID;
		$fixtures->append( new User( $user, get_user_blog_roles( $db, $user->ID ) ));
	}

	// Fix page translation owners (removed WP users that still own pages)
	fwrite(STDERR, "Fixing page translation creators.\n");
	foreach ( $fixtures->object_list as $key => $object ) {
		if ( $object->model == "cms.pagetranslation") {
			if ( ! in_array( $object->fields["creator"], $user_id_list ) ) {
				$object->fields["creator"] = Null;
			}
		}
		$fixtures->object_list[$key] = $object;
	}

	fwrite(STDERR, "Dumping fixtures.");
	fclose($slug_map_file_resource);
	echo($fixtures->dump());
	?>
