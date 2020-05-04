<?php

/**
 * Retrieve the multisites defined in this network
 */
class RestApi_MultisitesV1 extends RestApi_ExtensionBase {

	const URL = 'multisites';

	public function register_routes($namespace) {
		parent::register_route($namespace,
			self::URL, '/', [
				'callback' => [$this, 'get_multisites']
			]);
	}

	public function get_multisites() {
		return array_map([$this, 'prepare_item'], get_sites(array('number'=>0)));
	}

	private function prepare_item($blog) {
		switch_to_blog($blog->blog_id);
		$result = [
			'id' => $blog->blog_id,
			'name' => get_blog_details()->blogname,
			'icon' => get_site_icon_url(),
			'cover_image' => get_header_image(),
			'color' => '#FFA000',
			'path' => $blog->path,
			'description' => get_bloginfo('description'),
			'live' => $blog->public and !$blog->spam and !$blog->deleted and !$blog->archived and !$blog->mature,
		];
		if (class_exists('IntegreatSettingsPlugin')) {
			$settings = apply_filters('ig-settings', null);
			$extras = apply_filters('ig-extras', false);
			$result = array_merge($result, [
				'ige-zip' => apply_filters('ig-settings-legacy', $settings, 'plz'),
				'ige-evts' => apply_filters('ig-settings-legacy', $settings, 'events'),
				'ige-pn' => apply_filters('ig-settings-legacy', $settings, 'push_notifications'),
				'ige-srl' => apply_filters('ig-extras-legacy', $extras, 'serlo-abc'),
				'ige-sbt' => apply_filters('ig-extras-legacy', $extras, 'sprungbrett', true),
				'ige-lr' => apply_filters('ig-extras-legacy', $extras, 'lehrstellen-radar'),
				'ige-ilb' => apply_filters('ig-extras-legacy', $extras, 'ihk-lehrstellenboerse', true),
				'ige-ipb' => apply_filters('ig-extras-legacy', $extras, 'ihk-praktikumsboerse', true),
				'ige-c4r' => '0',	// Career for Refugees
			]);
		}
		restore_current_blog();
		return $result;
	}

}

/* change header image size */
add_action('after_setup_theme', function () {
	add_theme_support('custom-header', apply_filters('custom_header_args', [
		'width' => 600,
		'height' => 450,
	]));
});
