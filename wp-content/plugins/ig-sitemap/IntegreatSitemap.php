<?php

class IntegreatSitemap {

	public function __construct() {
		add_action('rest_api_init', function () {
			register_rest_route( 'ig-sitemap/v1', 'sitemap.xml', [
				'methods' => WP_REST_Server::READABLE,
				'callback' => [$this, 'get_sitemap']
			]);
		});
	}

	public function get_sitemap() {
		header('Content-Type: application/xml');
		echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
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
?>
</urlset>
<?php
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
