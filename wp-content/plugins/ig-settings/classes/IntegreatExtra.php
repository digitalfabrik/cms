<?php

/*
 * This class represents a central extra and specifies the different attributes.
 * Each extra can be en- and disabled by each individual instance.
 * The class also provides some static functions to support the processing of the extras.
 *
 */

class IntegreatExtra {

	public $id;
	public $name;
	public $alias;
	public $url;
	public $post;
	public $thumbnail;

	public function __construct($extra = []) {
		$extra = (object) $extra;
		$this->id = isset($extra->id) ? (int) $extra->id : null;
		$this->name = isset($extra->name) && $extra->name !== ''  ? htmlspecialchars($extra->name) : null;
		$this->alias = isset($extra->alias) && $extra->alias !== ''  ? htmlspecialchars($extra->alias) : null;
		$this->url = isset($extra->url) && $extra->url !== ''  ? htmlspecialchars($extra->url) : null;
		$this->post = isset($extra->post) && $extra->post !== '' ? str_replace('&qout;', '"', $extra->post) : null;
		$this->thumbnail = isset($extra->thumbnail) && $extra->thumbnail !== '' ? htmlspecialchars($extra->thumbnail) : null;
	}

	public function validate() {
		global $wpdb;
		if ($this->id) {
			if ($wpdb->query($wpdb->prepare('SELECT id from ' . self::get_table_name()." WHERE id = %d", $this->id)) !== 1) {
				$_SESSION['ig-admin-notices'][] = [
					'type' => 'error',
					'message' => 'There is no extra with the id "' . $this->id . '"'
				];
				$_SESSION['ig-current-error'][] = 'id';
				return false;
			}
		}
		if (!$this->name) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'You have to specify a name for this extra'
			];
			$_SESSION['ig-current-error'][] = 'name';
		}
		if (!$this->alias) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'You have to specify an alias for this extra'
			];
			$_SESSION['ig-current-error'][] = 'alias';
		}
		if (!$this->url) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'You have to specify a URL for this extra'
			];
			$_SESSION['ig-current-error'][] = 'url';
		} elseif (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'The given URL "' . $this->url . '" is not valid'
			];
			$_SESSION['ig-current-error'][] = 'url';
		}
		if ($this->post && !json_decode($this->post)) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'The post-values "' . $this->post . '" are no valid json'
			];
			$_SESSION['ig-current-error'][] = 'post';
		}
		if (!$this->thumbnail) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'You have to specify a thumbnail-URL for this extra'
			];
			$_SESSION['ig-current-error'][] = 'thumbnail';
		} elseif (filter_var($this->thumbnail, FILTER_VALIDATE_URL) === false) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'The given thumbnail-URL "' . $this->thumbnail . '" is not valid'
			];
			$_SESSION['ig-current-error'][] = 'thumbnail';
		}
		if (isset($_SESSION['ig-current-error'])) {
			return false;
		} else {
			return true;
		}
	}

	public function save() {
		global $wpdb;
		if ($this->id) {
			$extra_array = (array) $this;
			unset($extra_array['id']);
			return $wpdb->update(self::get_table_name(), $extra_array, ['id' => $this->id]);
		} else {
			return $wpdb->insert(self::get_table_name(), (array) $this);
		}
	}

	public function delete() {
		global $wpdb;
		return $wpdb->delete(self::get_table_name(), ['id' => $this->id]);
	}

	public static function get_table_name() {
		return $GLOBALS['wpdb']->base_prefix . 'ig_extras';
	}

	public static function get_extra_by_id($id) {
		global $wpdb;
		$extra = $wpdb->get_row($wpdb->prepare('SELECT * from ' . self::get_table_name() . ' WHERE id = %d', $id));
		if ($extra) {
			return new IntegreatExtra($extra);
		} else {
			return false;
		}
	}

	public static function get_extra_by_alias($alias) {
		global $wpdb;
		$extra = $wpdb->get_row($wpdb->prepare('SELECT * from ' . self::get_table_name() . ' WHERE alias = %s', $alias));
		if ($extra) {
			return new IntegreatExtra($extra);
		} else {
			return false;
		}
	}

	public static function get_extras() {
		global $wpdb;
		return array_map(function ($extra) {
			return new IntegreatExtra($extra);
		},
			$wpdb->get_results('SELECT * from ' . self::get_table_name())
		);
	}

	public static function get_default_extras() {
		return [
			new IntegreatExtra([
				'name' => 'Serlo',
				'alias' => 'serlo',
				'url' => 'https://abc-app.serlo.org/',
				'thumbnail' => 'https://cms.integreat-app.de/wp-content/uploads/extra-thumbnails/serlo.jpg'
			]),
			new IntegreatExtra([
				'name' => 'Sprungbrett',
				'alias' => 'sprungbrett',
				'url' => 'https://www.sprungbrett-intowork.de/ajax/app-search-internships?location={location}',
				'thumbnail' => 'https://cms.integreat-app.de/wp-content/uploads/extra-thumbnails/sprungbrett.jpg'
			]),
			new IntegreatExtra([
				'name' => 'Lehrstellenradar',
				'alias' => 'lehrstellenradar',
				'url' => 'https://www.lehrstellen-radar.de/5100,0,lsrsearch.html',
				'post' => json_encode([
					'partner' => '0006',
					'radius' => '50',
					'plz' => '{plz}',
				]),
				'thumbnail' => 'https://cms.integreat-app.de/wp-content/uploads/extra-thumbnails/lehrstellenradar.jpg'
			]),
			new IntegreatExtra([
				'name' => 'IHK Lehrstellenbörse',
				'alias' => 'ihk_lehrstellenboerse',
				'url' => 'https://www.ihk-lehrstellenboerse.de/joboffers/search.html?location={plz}&distance=1',
				'thumbnail' => 'https://cms.integreat-app.de/wp-content/uploads/extra-thumbnails/ihk_lehrstellenboerse.jpg'
			]),
			new IntegreatExtra([
				'name' => 'IHK Praktikumsbörse',
				'alias' => 'ihk_praktikumsboerse',
				'url' => 'https://www.ihk-lehrstellenboerse.de/joboffers/searchTrainee.html?location={plz}&distance=1',
				'thumbnail' => 'https://cms.integreat-app.de/wp-content/uploads/extra-thumbnails/ihk_praktikumsboerse.jpg'
			])
		];
	}
	
	public static function create_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
				  id mediumint(9) NOT NULL AUTO_INCREMENT,
				  name text NOT NULL,
				  alias text NOT NULL,
				  url text NOT NULL,
				  post text DEFAULT NULL,
				  thumbnail text NOT NULL,
				  PRIMARY KEY  (id)
				) $charset_collate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);
		if (isset($result[self::get_table_name()]) && $result[self::get_table_name()] == 'Created table ' . self::get_table_name()) {
			foreach(self::get_default_extras() as $extra) {
				if ($extra->validate()) {
					$extra->save();
				}
			}
		}
	}

	public static function form($form) {
		if ($form === 'select') {
			return self::get_select_form();
		} elseif ($form === 'extra') {
			return self::get_extra_form();
		} else {
			return false;
		}
	}

	private static function get_select_form() {
		$select_form = '<form action="' . $_SERVER['REQUEST_URI'] . '" method="GET">';
		foreach ($_GET as $key => $value) {
			$select_form .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
		}
		$select_form .= '<select name="id">';
		foreach(self::get_extras() as $extra) {
			$select_form .= '<option value="' . $extra->id . '" ' . (isset($_GET['id']) && $_GET['id'] === $extra->id ? 'selected' : '') . '>' . $extra->name . '</option>';
		}
		$select_form .= '</select><input class="button" type="submit" name="submit" value=" Edit "></form>';
		return $select_form;
	}

	private static function get_extra_form() {
		$extra = isset($_SESSION['ig-current-extra']) ? $_SESSION['ig-current-extra'] : false;
		$error = isset($_SESSION['ig-current-error']) ? $_SESSION['ig-current-error'] : [];
		$extra_form = '
			<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
				<input type="hidden" name="extra[id]" value="' . ($extra ? $extra->id : '') . '">
				<div>
					<label for="extra[name]">Name <b>*</b></label>
					<input type="text" class="' . ($error ? (in_array('name', $error) ? 'ig-error' : 'ig-success') : '') . '" name="extra[name]" value="' . ($extra ? (in_array('name', $error) ? htmlspecialchars($_POST['extra']['name']) : $extra->name) : '') . '">
				</div>
				<br>
				<div>
					<label for="extra[alias]">Alias <b>*</b></label>
					<input type="text" class="' . ($error ? (in_array('alias', $error) ? 'ig-error' : 'ig-success') : '') . '" name="extra[alias]" value="' . ($extra ? (in_array('alias', $error) ? htmlspecialchars($_POST['extra']['alias']) : $extra->alias) : '') . '">
				</div>
				<br>
				<div>
					<label for="extra[url]">URL <b>*</b></label>
					<input type="text" class="' . ($error ? (in_array('url', $error) ? 'ig-error' : 'ig-success') : '') . '" name="extra[url]" value="' . ($extra ? (in_array('url', $error) ? htmlspecialchars($_POST['extra']['url']) : $extra->url) : '') . '">
				</div>
				<br>
				<div>
					<label for="extra[post]">Post-Values for URL</label>
					<input type="text" class="' . ($error ? (in_array('post', $error) ? 'ig-error' : 'ig-success') : '') . '" name="extra[post]" value="' . ($extra ? str_replace('"', '&quot;', (in_array('post', $error) ? stripcslashes($_POST['extra']['post']) : $extra->post)) : '') . '">
				</div>
				<br>
				<div>
					<label for="extra[thumbnail]">Thumbnail-URL <b>*</b></label>
					<input type="text" class="' . ($error ? (in_array('thumbnail', $error) ? 'ig-error' : 'ig-success') : '') . '" name="extra[thumbnail]" value="' . ($extra ? (in_array('thumbnail', $error) ? htmlspecialchars($_POST['extra']['thumbnail']) : $extra->thumbnail) : '') . '">
				</div>
				<br>
				<input class="button button-primary" type="submit" name="submit" value=" Save ">
		';
		if ((!isset($_GET['action']) || $_GET['action'] !== 'create') && $extra) {
			$extra_form .= '
				<input class="button button-delete" type="submit" name="submit" value=" Delete " onclick="return confirm(\'Are you really sure you want to delete the extra &quot;' . htmlspecialchars($extra->name) . '&quot;? \')">
			';
		}
		$extra_form .= '
			</form>
			<p>Hint: You can use the placeholders {location} and {plz} for location-dependent links.</p>
		';
		return $extra_form;
	}

	public static function handle_request() {
		if (isset($_GET['submit'])) {
			self::handle_get_request();
		}
		if (isset($_POST['submit'])) {
			self::handle_post_request();
		}
	}

	private static function handle_get_request() {
		if (!isset($_GET['id'])) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'Form was not submitted properly (GET-parameter "id" is missing)'
			];
			return false;
		}
		$_SESSION['ig-current-extra'] = self::get_extra_by_id($_GET['id']);
		return true;
	}

	private static function handle_post_request() {
		if (!isset($_POST['extra'])) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'Form was not submitted properly (POST-parameter "extra" is missing)'
			];
			return false;
		}
		$extra = new IntegreatExtra(stripslashes_deep($_POST['extra']));
		if ($_POST['submit'] == ' Delete ') {
			$deleted = $extra->delete();
			if ($deleted !== 1) {
				$_SESSION['ig-admin-notices'][] = [
					'type' => 'error',
					'message' => 'Extra could not be deleted'
				];
				$_SESSION['ig-current-extra'] = $extra;
				return false;
			}
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'success',
				'message' => 'Extra successfully deleted'
			];
			unset($_SESSION['ig-current-extra']);
			return true;
		}
		if (!$extra->validate()) {
			$_SESSION['ig-current-extra'] = $extra;
			return false;
		}
		$saved = $extra->save();
		if ($saved === false) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'error',
				'message' => 'Extra could not be saved'
			];
			$_SESSION['ig-current-extra'] = $extra;
			return false;
		}
		if ($saved === 0) {
			$_SESSION['ig-admin-notices'][] = [
				'type' => 'info',
				'message' => 'Extra has not been changed'
			];
			$_SESSION['ig-current-extra'] = $extra;
			return false;
		}
		$_SESSION['ig-admin-notices'][] = [
			'type' => 'success',
			'message' => 'Extra saved successfully'
		];
		return true;
	}

}