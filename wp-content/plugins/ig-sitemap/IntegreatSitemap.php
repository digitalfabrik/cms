<?php

class IntegreatSitemap {

	const HOST = 'https://integreat.app'; // desired host for permalinks
	const XHTML_ENABLED = true; // XHTML alternative languages for Google

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
    <loc><?= str_replace(['https://cms.integreat-app.de', '&'], [self::HOST, '&amp;'], get_permalink($page)) ?></loc>
<?php
			if (self::XHTML_ENABLED) {
				$current_language = apply_filters('wpml_current_language', null);
				$languages = $wpdb->get_col("SELECT code FROM {$wpdb->prefix}icl_languages WHERE active = 1");
				foreach ($languages as $language) {
					$id = apply_filters('wpml_object_id', $page->ID, $page->post_type, false, $language);
					if ($id != null) {
						do_action('wpml_switch_language', $language);
?>
    <xhtml:link rel="alternate" hreflang="<?= $language ?>" href="<?= str_replace(['https://cms.integreat-app.de', '&'], [self::HOST, '&amp;'], get_permalink($id)) ?>"/>
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
			if (!$site->public || $site->spam || $site->deleted || $site->archived || $site->mature || apply_filters('ig-site-disabled', $site)) {
				continue;
			}
			switch_to_blog($site->blog_id);
			$languages = array_keys( apply_filters( 'wpml_active_languages', null, '' ) );
			foreach ($languages as $language) {
				do_action('wpml_switch_language', $language);
				$query = new WP_Query([
					'post_type' => ['page', 'event', 'disclaimer'],
					'post_status' => 'publish',
					'orderby' => 'modified',
					'posts_per_page' => 1,
				]);
				if ( $query->have_posts() ) {
					$last_modified = $query->posts[0]->post_modified_gmt;
				} else {
					continue;
				}
?>
  <sitemap>
    <loc><?= self::HOST.$site->path.$language.'/sitemap.xml' ?></loc>
    <lastmod><?= date('c', strtotime($last_modified)) ?></lastmod>
  </sitemap>
<?php
			}
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
			return '0.3';
		}
		return '1.0';
	}

}
