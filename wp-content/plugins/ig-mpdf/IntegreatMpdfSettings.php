<?php

class IntegreatMpdfSettings {

	/**
	 * Options page callback
	 */
	public static function create_admin_page() {
		$pages = get_pages(array('sort_column' => 'menu_order'));
		// run custom js script
		wp_enqueue_script('ig-mpdf-settings', IG_MPDF_PATH . 'custom.js');

        // include custom css file
		$css_file = __DIR__ . '/css/styles.css';
		if (is_file($css_file)) {
			echo '<style>' . file_get_contents($css_file) . '</style>';
		}
		?>
		<div class="wrap">
			<h1>PDF Export</h1>
			<form method="post" action="admin.php?page=ig-mpdf" id="ig-mpdf-form">
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
						<div class="page" style="padding-left:<?= count(get_post_ancestors($page)) * 20; ?>px;">
							<label for="page<?= $i; ?>">
								<input id="page<?= $i; ?>" type="checkbox" name="page[]" value="<?= $page->ID; ?>"/>
								<?= $page->post_title; ?>
                                <?php
									if (apply_filters('wpml_current_language', null) !== 'de') {
											echo '(' . get_the_title(apply_filters('wpml_object_id', $page->ID, 'page', true, 'de')) . ')';
									}
								?>
							</label>
						</div>
						<?php $i++; ?>
					<?php endforeach; ?>
				</div>
                <p class="submit"><input type="submit" name="submit" id="ig-mpdf-submit" class="button button-primary" value="Exportieren"/></p>
			</form>
		</div>
		<?php
	}

}