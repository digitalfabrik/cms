<?php

class IntegreatSitemap {

	const HOST = 'https://web.integreat-app.de';
	const XHTML_ENABLED = false;

	public function __construct() {
		add_action('rest_api_init', function () {
			register_rest_route( 'ig-sitemap/v1', 'sitemap.xml', [
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_sitemap']
			]);
		});
		add_action('rest_api_init', function () {
			register_rest_route( 'ig-sitemap/v1', 'sitemap-index.xml', [
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_sitemap_index']
			]);
		});
	}

	public function get_sitemap() {
		header('Content-Type: application/xml');
		global $wpdb;
		if (self::XHTML_ENABLED) {
			echo '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL.'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
		} else {
			echo '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL.'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		}
		$pages = (new WP_Query([
			'post_type' => ['page', 'event', 'disclaimer'],
			'post_status' => 'publish',
			'posts_per_page' => -1,
		]))->posts;
		foreach ($pages as $page) {
?>
  <url>
    <loc><?= get_permalink($page) ?></loc>
<?php
			if (self::XHTML_ENABLED) {
				$current_language = apply_filters('wpml_current_language', null);
				$languages = $wpdb->get_col("SELECT code FROM {$wpdb->prefix}icl_languages WHERE active = 1");
				foreach ($languages as $language) {
					$id = apply_filters('wpml_object_id', $page->ID, $page->post_type, false, $language);
					if (false && $id != null) {
						do_action('wpml_switch_language', $language);
?>
    <xhtml:link rel="alternate" hreflang="<?= $language ?>" href="<?= get_permalink($id) ?>"/>
<?php
					}
				}
				do_action('wpml_switch_language', $current_language);
			}
?>
    <lastmod><?= date('c', strtotime($page->post_modified_gmt)) ?></lastmod>
    <changefreq><?= $this->changefreq($page) ?></changefreq>
    <priority><?= $this->get_priority($page) ?></priority>
  </url>
<?php
			}
		echo '</urlset>';
		exit();
	}

	public function get_sitemap_index() {
		header('Content-Type: application/xml');
		global $wpdb;
		echo '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL.'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach (get_sites() as $site) {
			if (!$site->public || $site->spam || $site->deleted || $site->archived || $site->mature) {
				continue;
			}
			switch_to_blog($site->blog_id);
			$current_language = apply_filters('wpml_current_language', null);
			$languages = $wpdb->get_col("SELECT code FROM {$wpdb->prefix}icl_languages WHERE active = 1");
			foreach ($languages as $language) {
				do_action('wpml_switch_language', $language);
				$last_modified = (new WP_Query([
					'post_type' => ['page', 'event', 'disclaimer'],
					'post_status' => 'publish',
					'orderby' => 'modified',
					'posts_per_page' => 1,
				]))->posts[0]->post_modified_gmt;
?>
  <sitemap>
    <loc><?= self::HOST.$site->path.$language.'/sitemap.xml' ?></loc>
    <lastmod><?= date('c', strtotime($last_modified)) ?></lastmod>
  </sitemap>
<?php
			}
			do_action('wpml_switch_language', $current_language);
			restore_current_blog();
		}
		echo '</sitemapindex>';
		exit();
	}

	private function changefreq($page) {
		if ($page->post_type == 'event') {
			return 'daily';
		}
		return 'weekly';
	}

	private function get_priority($page) {
		if ($page->post_type == 'event') {
			return '0.8';
		}
		if ($page->post_content == '') {
			return '0.5';
		}
		return '1.0';
	}

}
