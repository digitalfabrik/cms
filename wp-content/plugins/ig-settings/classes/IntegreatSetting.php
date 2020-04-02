<?php

/*
 * This class represents a central setting and specifies its name, alias and type.
 * Each setting can be configured to a specific value by each individual instance.
 * The class also provides some static functions to support the processing of the settings.
 *
 */

class IntegreatSetting {

	public $id;
	public $name;
	public $alias;
	public $type;
	public static $current_setting = [];
	public static $current_error = [];

	public function __construct($setting = []) {
		$setting = (object) $setting;
		$this->id = isset($setting->id) ? (int) $setting->id : null;
		$this->name = isset($setting->name) && $setting->name !== '' ? $setting->name : null;
		$this->alias = isset($setting->alias) && $setting->alias !== '' ? $setting->alias : null;
		$this->type = isset($setting->type) && in_array($setting->type, ['string', 'bool', 'json', 'float', 'media']) ? $setting->type : null;
	}

	private function validate() {
		if ($this->id) {
			if (self::get_setting_by_id($this->id) === false) {
				IntegreatSettingsPlugin::$admin_notices[] = [
					'type' => 'error',
					'message' => 'There is no setting with the id "' . $this->id . '"'
				];
				self::$current_error[] = 'id';
				return false;
			}
		}
		if (!$this->name) {
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'error',
				'message' => 'You have to specify a name for this setting'
			];
			self::$current_error[] = 'name';
		}
		if (!$this->alias) {
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'error',
				'message' => 'You have to specify an alias for this setting'
			];
			self::$current_error[] = 'alias';
		}
		if (!in_array($this->type, ['string', 'bool', 'json', 'float', 'media'])) {
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'error',
				'message' => 'The given type "' . htmlspecialchars($this->type) . '" is not valid (must be "string", "bool", "json", "float", or "media")'
			];
			self::$current_error[] = 'type';
		}
		if (empty(self::$current_error)) {
			return true;
		} else {
			return false;
		}
	}

	private function save() {
		global $wpdb;
		if ($this->id) {
			$setting_array = (array) $this;
			unset($setting_array['id']);
			return $wpdb->update(self::get_table_name(), $setting_array, ['id' => $this->id]);
		} else {
			return $wpdb->insert(self::get_table_name(), (array) $this);
		}
	}

	private function delete() {
		global $wpdb;
		return $wpdb->delete(self::get_table_name(), ['id' => $this->id]);
	}

	public static function get_table_name() {
		return $GLOBALS['wpdb']->base_prefix . 'ig_settings';
	}

	public static function get_setting_by_id($id) {
		global $wpdb;
		$setting = $wpdb->get_row($wpdb->prepare('SELECT * from ' . self::get_table_name() . ' WHERE id = %d', $id));
		if ($setting) {
			return new IntegreatSetting($setting);
		} else {
			return false;
		}
	}

	public static function get_setting_by_alias($alias) {
		global $wpdb;
		$setting = $wpdb->get_row($wpdb->prepare('SELECT * from ' . self::get_table_name() . ' WHERE alias = %s', $alias));
		if ($setting) {
			return new IntegreatSetting($setting);
		} else {
			return false;
		}
	}

	public static function get_settings() {
		global $wpdb;
		return array_map(function ($setting) {
				return new IntegreatSetting($setting);
			},
			$wpdb->get_results('SELECT * from ' . self::get_table_name())
		);
	}

	private static function get_default_settings() {
		return [
			new IntegreatSetting([
				'name' => 'Location Prefix',
				'alias' => 'prefix',
				'type' => 'string'
			]),
			new IntegreatSetting([
				'name' => 'Location Name',
				'alias' => 'name_without_prefix',
				'type' => 'string'
			]),
			new IntegreatSetting([
				'name' => 'Location Name in Extra URLs',
				'alias' => 'location_override',
				'type' => 'string'
			]),
			new IntegreatSetting([
				'name' => 'PLZ',
				'alias' => 'plz',
				'type' => 'string'
			]),
			new IntegreatSetting([
				'name' => 'Hide Location in App',
				'alias' => 'hidden',
				'type' => 'bool'
			]),
			new IntegreatSetting([
				'name' => 'Completely disable Location in API',
				'alias' => 'disabled',
				'type' => 'bool'
			]),
			new IntegreatSetting([
				'name' => 'Events',
				'alias' => 'events',
				'type' => 'bool'
			]),
            new IntegreatSetting([
                'name' => 'Pushnachrichten',
                'alias' => 'push-notifications',
                'type' => 'bool'
            ]),
            new IntegreatSetting([
                'name' => 'Logo',
                'alias' => 'logo',
                'type' => 'media'
            ]),
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
				  type text NOT NULL,
				  PRIMARY KEY  (id)
				) $charset_collate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);
		if (isset($result[self::get_table_name()]) && $result[self::get_table_name()] == 'Created table ' . self::get_table_name()) {
			foreach(self::get_default_settings() as $setting) {
				if ($setting->validate()) {
					$setting->save();
				}
			}
		}
	}

	public static function form($form) {
		if ($form === 'select') {
			return self::get_select_form();
		} elseif ($form === 'setting') {
			return self::get_setting_form();
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
		foreach(self::get_settings() as $setting) {
			$select_form .= '<option value="' . $setting->id . '" ' . (isset($_GET['id']) && $_GET['id'] === $setting->id ? 'selected' : '') . '>' . htmlspecialchars($setting->name) . '</option>';
		}
		$select_form .= '</select><input class="button" type="submit" name="submit" value=" Edit "></form><br>';
		return $select_form;
	}

	private static function get_setting_form() {
		$setting = IntegreatSettingsPlugin::encode_quotes_deep(self::$current_setting);
		$setting_post = IntegreatSettingsPlugin::encode_quotes_deep(stripslashes_deep($_POST['setting']));
		$setting_form = '
			<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
				<input type="hidden" name="setting[id]" value="' . ($setting ? $setting->id : '') . '">
				<div>
					<label for="setting[name]">Name <strong>*</strong></label>
					<input type="text" class="' . (!empty(self::$current_error) ? (in_array('name', self::$current_error) ? 'ig-error' : 'ig-success') : '') . '" name="setting[name]" value="' . ($setting ? (in_array('name', self::$current_error) ? $setting_post->name : $setting->name) : '') . '">
				</div>
				<br>
				<div>
					<label for="setting[alias]">Alias <strong>*</strong></label>
					<input type="text" class="' . (!empty(self::$current_error) ? (in_array('alias', self::$current_error) ? 'ig-error' : 'ig-success') : '') . '" name="setting[alias]" value="' . ($setting ? (in_array('alias', self::$current_error) ? $setting_post->alias : $setting->alias) : '') . '">
				</div>
				<br>
				<div>
					<label for="type">Type</label>
					<select name="setting[type]">
						<option value="string" ' . ($setting && $setting->type === 'string' ?  'selected' : '') . '>String</option>
						<option value="bool" ' . ($setting && $setting->type === 'bool' ? 'selected' : '') . '>Boolean</option>
						<option value="json" ' . ($setting && $setting->type === 'json' ? 'selected' : '') . '>JSON</option>
						<option value="float" ' . ($setting && $setting->type === 'float' ? 'selected' : '') . '>Float</option>
					</select>
				</div>
				<br>
				<input class="button button-primary" type="submit" name="submit" value=" Save ">
		';
		if ((!isset($_GET['action']) || $_GET['action'] !== 'create_setting') && $setting) {
			$setting_form .= '
				<input class="button button-delete" type="submit" name="submit" value=" Delete " onclick="return confirm(\'Are you really sure you want to delete the setting &quot;' . htmlspecialchars($setting->name) . '&quot;?\nThis will also delete the configurations of all locations.\')">
			';
		}
		$setting_form .= '
			</form>
		';
		return $setting_form;
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
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'error',
				'message' => 'Form was not submitted properly (GET-parameter "id" is missing)'
			];
			return false;
		}
		self::$current_setting = self::get_setting_by_id($_GET['id']);
		return true;
	}

	private static function handle_post_request() {
		if (!isset($_POST['setting'])) {
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'error',
				'message' => 'Form was not submitted properly (POST-parameter "setting" is missing)'
			];
			return false;
		}
		$setting = new IntegreatSetting(IntegreatSettingsPlugin::decode_quotes_deep(stripslashes_deep($_POST['setting'])));
		self::$current_setting = $setting;
		if ($_POST['submit'] == ' Delete ') {
			$deleted = $setting->delete();
			if ($deleted !== 1) {
				IntegreatSettingsPlugin::$admin_notices[] = [
					'type' => 'error',
					'message' => 'Setting could not be deleted'
				];
				return false;
			}
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'success',
				'message' => 'Setting successfully deleted'
			];
			self::$current_setting = [];
			return true;
		}
		if (!$setting->validate()) {
			return false;
		}
		$saved = $setting->save();
		if ($saved === false) {
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'error',
				'message' => 'Setting could not be saved'
			];
			return false;
		}
		if ($saved === 0) {
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'info',
				'message' => 'Setting has not been changed'
			];
			return false;
		}
		IntegreatSettingsPlugin::$admin_notices[] = [
			'type' => 'success',
			'message' => 'Setting saved successfully'
		];
		self::$current_setting = [];
		return true;
	}

}
