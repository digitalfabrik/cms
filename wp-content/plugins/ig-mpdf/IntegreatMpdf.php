<?php

use Mpdf\Mpdf;
use Mpdf\MpdfException;

class IntegreatMpdf {
	private $mpdf;
	private $pages;
	private $language;
	private $toc;
	private $file_url;
	private $file_path;

	function __construct($pages, $language, $toc = true) {
		$this->pages = $pages;
		$this->language = $language;
		$this->toc = $toc && count($pages) > 1;
		$file_path = '/wp-content/uploads/ig-mpdf-cache/' . get_bloginfo('name') . '-' . $this->language . ($this->toc ? '-toc-' : '-') . md5(implode(',', $this->pages)) . '.pdf';
		$this->file_path = dirname(__FILE__, 4) . $file_path;
		$this->file_url = get_site_url(null, $file_path);

		// init mpdf
		require_once __DIR__ . '/vendor/autoload.php';
		$init_options = [
				'margin_top' => 20,
				'margin_left' => 20,
				'margin_right' => 20,
				'margin_bottom' => 26,
				'tempDir' => dirname(__FILE__, 3) . '/uploads/ig-mpdf-cache/tmp',
				'fontTempDir' => dirname(__FILE__, 3) . '/uploads/ig-mpdf-cache/tmp/ttfontdata',
				'autoScriptToLang' => true,
				'autoLangToFont' => true
		];
		$this->mpdf = new Mpdf($init_options);
	}

	/**
	 * Decides whether to create a new pdf file or use a cached one
	 *
	 * @throws MpdfException
	 */
	public function get_pdf() {
	   	if (!file_exists($this->file_path) || !$this->is_up_to_date($this->file_path)) {
			$this->create_pdf();
		}
		header('Location: ' . $this->file_url);
	}

	/**
	 * Creates a new pdf file for pages
	 *
	 * @throws MpdfException
	 */
	private function create_pdf() {
		// set rtl direction on arabic or farsi
		if(in_array(apply_filters('wpml_current_language', null), array('ar', 'fa'))) {
			$this->mpdf->SetDirectionality('rtl');
		}
		// head
		$head = '	<html>
						<head>
							<style>
								body {
									font-family: "sans-serif";
									font-size:   14px;
									color:	   #000000;
									line-height: 1.4;
								}
								.page {
									margin-bottom:  35px;
									padding-bottom: 35px;
								}
								.page-border {
									border-bottom: 3px solid #BBBBBB;
								}
								.header_footer {
									color: #666666;
								}
							</style>
						</head>
					<body>';
		$this->mpdf->WriteHTML($head);

		// TOC
		if ($this->toc) {
			switch ($this->language) {
				case 'en':
					$toc_title = 'Table of Contents';
					break;
				case 'fr':
					$toc_title = 'Contenu';
					break;
				case 'ar':
					$toc_title = 'محتويات';
					break;
				case 'fa':
					$toc_title = 'محتویات';
					break;
				default:
					$toc_title = 'Inhaltsverzeichnis';
			}
			$this->mpdf->TOCpagebreakByArray(['toc-preHTML' => '<h2>' . $toc_title . '</h2>', 'toc-odd-footer-value' => -1]);
		}

		// footer
		$footer = '	<table width="100%" style="margin-top: 20px;" class="header_footer">
						<tr>
							<td width="10%" valign="top" class="footer_text">{PAGENO}</td>
							<td width="40%" valign="top" align="center">'.get_bloginfo_rss("name").'</td>
							<td width="40%" valign="top" align="right" class="footer_text">
								<img src="'. __DIR__ .'/logo.png" width="auto" height="25px" />
							</td>
						</tr>
					</table>';
		$this->mpdf->setHTMLFooter($footer);

		// content
		$pages_iterator = new CachingIterator(new ArrayIterator($this->pages));
		foreach($pages_iterator as $page) {
			$post = get_post($page);
			if ($this->toc) {
				$this->mpdf->TOC_Entry(htmlspecialchars($post->post_title, ENT_QUOTES));
			}
			if ($pages_iterator->hasNext()) {
				$this->mpdf->WriteHTML('<div class="page page-border">');
			} else {
				$this->mpdf->WriteHTML('<div class="page">');
			}
			$this->mpdf->WriteHTML('<h2>'.$post->post_title.'</h2>'.wpautop($post->post_content).'</div>');
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
		foreach($this->pages as $page) {
			$page_modified = get_post_modified_time('G', true, $page);
			if($page_modified > $file_modified) {
				return false;
			}
		}
		return true;
	}
}