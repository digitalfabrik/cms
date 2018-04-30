<?php

class IntegreatMpdfSettings {

	private $pages;

	public function __construct() {
		$this->pages = get_pages(array('sort_column' => 'menu_order'));

		if (isset($_POST['submit'])) {
			if(isset($_POST['page'])) {
				$mpdf = new IntegreatMpdf($_POST['page'], apply_filters('wpml_current_language', null), isset($_POST['toc']));
				try {
					$mpdf->get_pdf();
				} catch (\Mpdf\MpdfException $e) {
					echo '<div class="notice notice-error is-dismissible"><p><strong>' . $e->getMessage() . '</strong></p></div>';
				}
			} else {
				echo '<div class="notice notice-warning is-dismissible"><p><strong>Bitte wählen Sie mindestens eine Seite aus.</strong></p></div>';
			}
		}

		wp_enqueue_script('ig-mpdf-settings', IG_MPDF_PATH . 'custom.js');
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		add_menu_page('PDF Export',
			'PDF Export',
			'read',
			'ig-mpdf',
			array( $this, 'create_admin_page' ),
			'dashicons-format-aside'
		);
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {
		$css_file = __DIR__ . '/css/styles.css';
		if (is_file($css_file)) {
			echo '<style>' . file_get_contents($css_file) . '</style>';
		}
		$pages = $this->add_pages_depth();
		?>
		<div class="wrap">
			<?php if(!empty($this->pdf_link)): ?>
				<?php if($this->pdf_link === false): ?>
					<div class="notice notice-error">
						<p>Bei der Erstellung der PDF-Datei ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<h1>PDF Export</h1>
			<form method="post" action="admin.php?page=ig-mpdf">
				<h3><div>
					<label for="toc">Inhaltsverzeichnis</label>
					<label class="switch">
						<input type="checkbox" name="toc" checked>
						<span class="slider round"></span>
					</label>
				</div></h3>
				<h3>Seiten zum Export auswählen:</h3>
				<div id="check-all">
					<div>
						<label for="check_all">
							<input type="checkbox" name="check_all" id="check_all" />
							Alle an-/abwählen
						</label>
					</div>
				</div>
				<div class="pages">
					<?php $i = 0; ?>
					<?php foreach($pages as $page): ?>
						<div class="page" style="padding-left:<?php echo $page['depth'] * 20; ?>px;">
							<label for="page<?php echo $i; ?>">
								<input id="page<?php echo $i; ?>" type="checkbox" name="page[]" value="<?php echo $page['id']; ?>" />
								<?php
									echo $page['title'];
									if (apply_filters('wpml_current_language', NULL) !== 'de') {
										$de = get_the_title(apply_filters('wpml_object_id', $page['id'], 'page', TRUE, 'de'));
										if (!empty($de)) {
											echo ' ('.$de.')';
										} elseif (apply_filters('wpml_current_language', NULL) !== 'en') {
											$en = get_the_title(apply_filters('wpml_object_id', $page['id'], 'page', TRUE, 'en'));
											if (!empty($en)) {
												echo ' ('.$en.')';
											}
										}
									}
								?>
							</label>
						</div>
						<?php $i++; ?>
					<?php endforeach; ?>
				</div>
				<?php submit_button('Exportieren'); ?>
			</form>
		</div>
		<style>
			.pages .page {
				margin-bottom: 5px;
			}
			#check-all {
				margin-bottom: 15px;
			}
			#check-all div {
				float: right;
			}
		</style>
		<?php
	}

	/**
	 * Create corresponding database table
	 */
	static function create_database_table() {
		global $wpdb;

		$sql = "
		--
		-- Table structure for table `wp_ig_mpdf`
		--
		
		CREATE TABLE IF NOT EXISTS `wp_ig_mpdf` (
		  `id` int(10) NOT NULL AUTO_INCREMENT,
		  `pdf_name` TEXT NOT NULL,
		  `pages` TEXT NOT NULL,
		  `creation_date` TIMESTAMP NOT NULL,
		  `multiple` TINYINT(2) NOT NULL,
		  `instance` int(10) NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";

		$wpdb->query($sql);
	}

	/**
	 * add depth level to pages
	 *
	 * @return array with pages (title, id, depth)
	 */
	private function add_pages_depth() {
		$out_pages = array();
		for($i = 0; $i < count($this->pages); $i++) {
			$out_pages[$i] = array(
				'id'	=> $this->pages[$i]->ID,
				'title' => $this->pages[$i]->post_title,
				'depth' => $this->estimate_page_depth($i),
			);
		}

		return $out_pages;
	}

	/**
	 * find depth level of a page
	 *
	 * @param Integer index
	 * @return mixed
	 */
	private function estimate_page_depth($index) {
		return $this->estimate_page_depth_recursion($this->pages[$index]->post_parent, 0);
	}

	/**
	 * find depth level of page (recursion)
	 *
	 * @param Integer post_id
	 * @param Integer level
	 * @return mixed level
	 */
	private function estimate_page_depth_recursion($post_id, $level) {
		if($post_id == 0) {
			return $level;
		}

		$index = 0;
		for($i = 0; $i < count($this->pages); $i++) {
			if($this->pages[$i]->ID == $post_id) {
				$index = $i;
				break;
			}
		}

		$level = $level + 1;
		return $this->estimate_page_depth_recursion($this->pages[$index]->post_parent, $level);
	}

}