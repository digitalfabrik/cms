<?php

use Mpdf\Mpdf;
use Mpdf\MpdfException;

class IntegreatMpdf {
	private $mpdf;
	private $page_ids;
	private $language;
	private $toc;
	private $file_path;
	private $file_name;

	function __construct($page_ids, $toc = true) {
		// init mpdf
		require_once __DIR__ . '/vendor/autoload.php';
		if (!file_exists(dirname(__FILE__, 3) . '/uploads/ig-mpdf-cache/tmp')) {
			mkdir(dirname(__FILE__, 3) . '/uploads/ig-mpdf-cache/tmp', 0775, true);
		}
		$this->mpdf = new Mpdf([
			'margin_top' => 20,
			'margin_left' => 20,
			'margin_right' => 20,
			'margin_bottom' => 26,
			'tempDir' => dirname(__FILE__, 3) . '/uploads/ig-mpdf-cache/tmp',
			'fontTempDir' => dirname(__FILE__, 3) . '/uploads/ig-mpdf-cache/tmp/ttfontdata',
			'autoScriptToLang' => true,
			'autoLangToFont' => true
		]);
		$this->page_ids = $page_ids;
		$this->toc = $toc && count($page_ids) > 1;
		$this->language = apply_filters('wpml_current_language', null);
		// set file path of cached file
		$this->file_path = dirname(__FILE__, 4) . '/wp-content/uploads/ig-mpdf-cache/' . get_bloginfo('name') . '-' . $this->language . ($this->toc ? '-toc-' : '-') . md5(implode(',', $this->page_ids)) . '.pdf';
		// get pdf title in german (if pdf consists of only one page or one category)
		$page_title = '';
		if (count($this->page_ids) == 1) {
			// set title of single page if there is only one page in the pdf
			$page_title = get_the_title(apply_filters('wpml_object_id', $this->page_ids[0], 'page', true, 'de'));
		} else {
			// get root pages if there is more than one page in the pdf
			$root_page_ids = array_filter($this->page_ids, function($page_id) {
				return get_post($page_id)->post_parent === 0;
			});
			// set title of root page if there is only one root page in the pdf
			if (count($root_page_ids) == 1) {
				$page_title = get_the_title(apply_filters('wpml_object_id', $root_page_ids[0], 'page', true, 'de'));
			}
		}
		// set title to be displayed in the browser
		$this->file_name = 'Integreat - ' . get_bloginfo('name') . ' - ' . $GLOBALS['sitepress']->get_display_language_name($this->language, 'de') . ($page_title ? ' - ' . $page_title : '');
	}

	/**
	 * Decides whether to create a new pdf file or use a cached one
	 *
	 * @throws MpdfException
	 */
	public function get_pdf() {
		// if there is no cached pdf or the cached pdf is outdated, generate a new one
	   	if (!file_exists($this->file_path) || !$this->is_up_to_date($this->file_path)) {
			$this->create_pdf();
		}
		// set headers to enable pdf output to browser
		header('Content-Type: application/pdf');
	   	header('Content-disposition: inline; filename="' . $this->file_name . '.pdf"');
		echo file_get_contents($this->file_path);
		exit();
	}

	/**
	 * Creates a new pdf file for pages
	 *
	 * @throws MpdfException
	 */
	private function create_pdf() {
		$this->mpdf->SetTitle($this->file_name);
		// set rtl direction on arabic or farsi
		if(in_array($this->language, array('ar', 'fa'))) {
			$this->mpdf->SetDirectionality('rtl');
		}
		// head
		$head = '<html><head>';
		// include custom css file
		$css_file = __DIR__ . '/css/pdf.css';
		if (is_file($css_file)) {
			$head .= '<style>' . file_get_contents($css_file) . '</style>';
		}
		$head .= '</head><body>';
		$this->mpdf->WriteHTML($head);

		// TOC
		if ($this->toc) {
			// TOC title
			switch ($this->language) {
				case 'de':
					$toc_title = 'Inhaltsverzeichnis';
					break;
				case 'fr':
					$toc_title = 'Contenu';
					break;
				case 'es':
					$toc_title = 'Contenido';
					break;
				case 'ku':
					$toc_title = 'Naveroka';
					break;
				case 'tr':
					$toc_title = 'Içindekiler';
					break;
				case 'po':
					$toc_title = 'Treść';
					break;
				case 'ro':
					$toc_title = 'Conținut';
					break;
				case 'ru':
					$toc_title = 'Cодержание';
					break;
				case 'sr':
					$toc_title = 'Cадржај';
					break;
				case 'ar':
					$toc_title = 'محتويات';
					break;
				case 'fa':
					$toc_title = 'محتویات';
					break;
				case 'am':
					$toc_title = 'ይዘቶች';
					break;
				case 'ti':
					$toc_title = 'ካርታ';
					break;
				default:
					$toc_title = 'Table of Contents';
			}
			// TOC footer
			$toc_footer = [
				'L' => [
					'content' => $toc_title,
					'font-size' => 10,
					'font-style' => 'B',
				],
				'C' => [
					'content' => get_bloginfo('name'),
					'font-size' => 14,
					'color'=>'#666666'
				],
				'R' => [
					'content' => '<img src="' . __DIR__ . '/logo.png" width="auto" height="25px" />',
				],
				'line' => 1
			];
			$this->mpdf->DefFooterByName('toc_footer', $toc_footer);
			$this->mpdf->TOCpagebreakByArray(['toc-preHTML' => '<h2>' . $toc_title . '</h2>', 'toc-odd-footer-name' => 'toc_footer', 'toc-odd-footer-value' => 1, 'links' => true]);
		}

		// footer
		$this->mpdf->SetFooter([
			'odd' => [
				'L' => [
					'content' => '{PAGENO}',
				],
				'C' => [
					'content' => get_bloginfo('name'),
					'font-size' => 14,
					'color'=>'#666666'
				],
				'R' => [
					'content' => '<img src="' . __DIR__ . '/logo.png" width="auto" height="25px" />',
				],
				'line' => 1
			]
		]);

		// content
		$pages_iterator = new CachingIterator(new ArrayIterator($this->page_ids));
		foreach($pages_iterator as $page_id) {
			$page = get_post($page_id);
			if ($pages_iterator->hasNext()) {
				$this->mpdf->WriteHTML('<div class="page page-border">');
			} else {
				$this->mpdf->WriteHTML('<div class="page">');
			}
			if ($this->toc) {
				$this->mpdf->TOC_Entry(htmlspecialchars($page->post_title, ENT_QUOTES), count(get_post_ancestors($page)));
			}
			// remove tel links because they can not be handled in the pdf
			$content_without_tel_links = preg_replace('/<a href="tel:.*">(\+?[\d\s]*)<\/a>/', '$1', $page->post_content);
			$this->mpdf->WriteHTML('<h2>' . $page->post_title . '</h2>' . wpautop($content_without_tel_links) . '</div>');
		}
		$this->mpdf->WriteHTML('</body></html>');

		// save/cache file
		$this->mpdf->Output($this->file_path, 'F');
	}

	/**
	 * Check if the file is newer than the pages it contains
	 *
	 * @param string
	 * @return bool
	 */
	private function is_up_to_date($filename) {
		$file_modified = filemtime($filename);
		foreach($this->page_ids as $page_id) {
			$page_modified = get_post_modified_time('G', true, $page_id);
			if($page_modified > $file_modified) {
				return false;
			}
		}
		return true;
	}
}