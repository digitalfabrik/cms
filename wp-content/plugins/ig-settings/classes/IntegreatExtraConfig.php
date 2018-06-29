<?php

/*
 * This class represents an extra configuration, which means the state of an extra - either enabled or disabled.
 * It also provides some static methods to support the processing of the extra configurations.
 */

class IntegreatExtraConfig {

	public $id;
	public $extra_id;
	public $enabled;
	public static $current_error = [];

	public function __construct($extra_config = []) {
		$extra_config = (object) $extra_config;
		$this->id = isset($extra_config->id) ? (int) $extra_config->id : null;
		$this->extra_id = isset($extra_config->extra_id) ? (int) $extra_config->extra_id : null;
		$this->enabled = isset($extra_config->enabled) ? (bool) $extra_config->enabled : false;
	}

	public function validate() {
		global $wpdb;
		if ($this->id) {
			if ($wpdb->query($wpdb->prepare('SELECT id from ' . self::get_table_name()." WHERE id = %d", $this->id)) !== 1) {
				IntegreatSettingsPlugin::$admin_notices[] = [
					'type' => 'error',
					'message' => 'There is no extra config with the id "' . $this->id . '"'
				];
				self::$current_error[] = 'id';
				return false;
			}
		}
		if (!$this->extra_id) {
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'error',
				'message' => 'You have to specify an extra id for this extra config'
			];
			self::$current_error[] = 'extra_id';
			return false;
		}
		$extra = IntegreatExtra::get_extra_by_id($this->extra_id);
		if ($extra === false) {
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'error',
				'message' => 'There is no extra with the id "' . $this->extra_id . '"'
			];
			self::$current_error[] = 'extra_id';
			return false;
		}
		$plz = $wpdb->get_var("SELECT value
			FROM {$wpdb->base_prefix}ig_settings
				AS settings
			JOIN {$wpdb->prefix}ig_settings_config
				AS config
				ON settings.id = config.setting_id
			WHERE settings.alias = 'plz'");
		if ($this->enabled && (strpos($extra->url, '{plz}') !== false || strpos($extra->post, '{plz}') !== false) && !$plz){
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'error',
				'message' => 'The extra "' . htmlspecialchars($extra->name) . '" can not be enabled because it depends on the setting "plz" for this location'
			];
			self::$current_error[] = 'enabled';
			return false;
		}
		return true;
	}

	public function save() {
		global $wpdb;
		if ($this->id) {
			$extra_setting_array = (array) $this;
			unset($extra_setting_array['id']);
			return $wpdb->update(self::get_table_name(), $extra_setting_array, ['id' => $this->id]);
		} else {
			return $wpdb->insert(self::get_table_name(), (array) $this);
		}
	}

	public function delete() {
		global $wpdb;
		return $wpdb->delete(self::get_table_name(), ['id' => $this->id]);
	}

	public static function get_table_name() {
		return $GLOBALS['wpdb']->prefix . 'ig_extras_config';
	}

	public static function get_extra_config_by_id($id) {
		global $wpdb;
		$extra_config = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::get_table_name() . ' WHERE extra_id = %d', $id));
		if ($extra_config) {
			return new IntegreatExtraConfig($extra_config);
		} else {
			return false;
		}
	}

	public static function get_extra_configs() {
		global $wpdb;
		return array_map(function ($extra_config) {
				return new IntegreatExtraConfig($extra_config);
			},
			$wpdb->get_results('SELECT * FROM ' . self::get_table_name())
		);
	}

	public static function get_default_extra_configs() {
		global $wpdb;
		// because the ige-* options might not be set or might be in the wrong format, we have to do many checks to prevent PHP errors
		$sprungbrett = json_decode($wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'ige-sbt'"));
		$ihk_lehrstellenboerse = json_decode($wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'ige-ilb'"));
		$ihk_praktikumsboerse = json_decode($wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'ige-ipb'"));
		return [
			new IntegreatExtraConfig([
				'extra_id' => IntegreatExtra::get_extra_by_alias('serlo-abc')->id,
				'enabled' => $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'ige-srl'")
			]),
			new IntegreatExtraConfig([
				'extra_id' => IntegreatExtra::get_extra_by_alias('sprungbrett')->id,
				'enabled' => (isset($sprungbrett->enabled) ? $sprungbrett->enabled : false)
			]),
			new IntegreatExtraConfig([
				'extra_id' => IntegreatExtra::get_extra_by_alias('lehrstellen-radar')->id,
				'enabled' => $wpdb->get_var("SELECT option_value FROM $wpdb->options WHERE option_name = 'ige-lr'")
			]),
			new IntegreatExtraConfig([
				'extra_id' => IntegreatExtra::get_extra_by_alias('ihk-lehrstellenboerse')->id,
				'enabled' => (isset($ihk_lehrstellenboerse->enabled) ? $ihk_lehrstellenboerse->enabled : false)
			]),
			new IntegreatExtraConfig([
				'extra_id' => IntegreatExtra::get_extra_by_alias('ihk-praktikumsboerse')->id,
				'enabled' => (isset($ihk_praktikumsboerse->enabled) ? $ihk_praktikumsboerse->enabled : false)
			]),
		];
	}

	public static function create_table() {
		global $wpdb;
		$table_name = self::get_table_name();
		$foreign_table_name = IntegreatExtra::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $table_name (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					extra_id mediumint(9) NOT NULL,
					enabled tinyint(1) DEFAULT 0 NOT NULL,
					PRIMARY KEY  (id),
					FOREIGN KEY (extra_id)
						REFERENCES $foreign_table_name(id)
						ON DELETE CASCADE
				) $charset_collate;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$result = dbDelta($sql);
		if (isset($result[self::get_table_name()]) && $result[self::get_table_name()] == 'Created table ' . self::get_table_name()) {
			foreach(self::get_default_extra_configs() as $extra_config) {
				if ($extra_config->validate()) {
					$extra_config->save();
				}
			}
		}
	}

	public static function form() {
		$form = '
			<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
		';
		foreach (IntegreatExtra::get_extras() as $extra) {
			if (!$extra_config = self::get_extra_config_by_id($extra->id)) {
				$extra_config = new IntegreatExtraConfig([
					'extra_id' => $extra->id
				]);
			}
			$form .= '
				<div>
					<label for="' . $extra->id . '">' . htmlspecialchars($extra->name) . '</label>
					<label class="switch">
						<input type="checkbox" name="' . $extra->id . '"' . ($extra_config->enabled ? ' checked' : '') . '>
						<span class="slider round"></span>
					</label>
				</div><br><br><br>
			';
		}
		$form .= '
				<input class="button button-primary" type="submit" name="submit" value=" Save ">
			</form>
		';
		return $form;
	}

	public static function handle_request() {
		if (isset($_POST['submit'])) {
			$changes_made = false;
			$error_occurred = false;
			foreach (IntegreatExtra::get_extras() as $extra) {
				if (!$extra_config = self::get_extra_config_by_id($extra->id)) {
					$extra_config = new IntegreatExtraConfig([
						'extra_id' => $extra->id
					]);
				}
				if (isset($_POST[$extra_config->extra_id])) {
					$extra_config->enabled = true;
				} else {
					$extra_config->enabled = false;
				}
				if (!$extra_config->validate()) {
					$error_occurred = true;
					continue;
				}
				$saved = $extra_config->save();
				if ($saved === false) {
					IntegreatSettingsPlugin::$admin_notices[] = [
						'type' => 'error',
						'message' => 'Extra "' . htmlspecialchars(IntegreatExtra::get_extra_by_id($extra_config->extra_id)->name) . '" could not be ' . ($extra_config->enabled ? 'enabled' : 'disabled')
					];
					$error_occurred = true;
					continue;
				}
				if ($saved !== 0) {
					$changes_made = true;
					continue;
				}
			}
			if ($error_occurred) {
				return false;
			}
			if (!$changes_made) {
				IntegreatSettingsPlugin::$admin_notices[] = [
					'type' => 'info',
					'message' => 'Extra configuration has not been changed'
				];
				return false;
			}
			IntegreatSettingsPlugin::$admin_notices[] = [
				'type' => 'success',
				'message' => 'Extra configuration saved successfully'
			];
		}
		return true;
	}

}