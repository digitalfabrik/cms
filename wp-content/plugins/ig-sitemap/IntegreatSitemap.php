<?php

class IntegreatSitemap {

    const HOST = 'https://web.integreat-app.de';

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
		echo '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL.'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		$pages = (new WP_Query([
			'post_type' => ['page', 'event', 'disclaimer'],
			'post_status' => 'publish',
			'posts_per_page' => -1,
		]))->posts;
		foreach ($pages as $page) {
?>
  <url>
    <loc><?= get_permalink($page) ?></loc>
    <lastmod><?= $page->post_modified_gmt ?></lastmod>
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
		global $sitepress;
		echo '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL.'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach (get_sites() as $site) {
		    if (!$site->public || $site->spam || $site->deleted || $site->archived || $site->mature) {
		        continue;
            }
			switch_to_blog($site->blog_id);
		    foreach (apply_filters('wpml_active_languages', null, '') as $language) {
			    $sitepress->switch_lang($language['code'], true);
			    $last_modified = (new WP_Query([
				    'post_type' => ['page', 'event', 'disclaimer'],
				    'post_status' => 'publish',
				    'orderby' => 'modified_gmt',
				    'posts_per_page' => 1,
			    ]))->posts[0]->post_modified_gmt;
?>
  <sitemap>
    <loc><?= self::HOST.$site->path.$language['code'].'/sitemap.xml' ?></loc>
    <lastmod><?= $last_modified ?></lastmod>
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
		    return '0.5';
        }
        return '1.0';
	}

}
