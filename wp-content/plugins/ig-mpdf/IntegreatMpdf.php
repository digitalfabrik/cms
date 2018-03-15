<?php

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class IntegreatMpdf {
    private $mpdf;
    private $config;
    private $pages;
    private $instance;
    private $original_instance;
    private $file_path;
    private $language;

    function __construct($pages, $instance, $language = 'de') {
		// init mpdf
		require_once __DIR__ . '/vendor/autoload.php';
        $this->set_pages($pages);
        $this->instance = $instance;
        $this->original_instance = get_current_blog_id();
        $this->file_path = 'wp-content/uploads/ig-mpdf-cache/';
        $this->language = $language;
        $this->config = array(
            'init_options' => array(
                'margin_top' => 20,
                'margin_left' => 20,
                'margin_right' => 20,
                'margin_bottom' => 26,
                'fontDir' => [
					(new ConfigVariables())->getDefaults()['fontDir'],
                	__DIR__ . '/fonts',
				],
                'tempDir' => dirname(__FILE__, 3) . '/uploads/ig-mpdf-cache/tmp',
                'fontTempDir' => dirname(__FILE__, 3) . '/uploads/ig-mpdf-cache/tmp/ttfontdata',
                'fontdata' => (new FontVariables())->getDefaults()['fontdata'] + [
                    "noto_sans" => [
                        'R' => "NotoSans-Regular.ttf",
                        'B' => "NotoSans-Bold.ttf",
                    ],
                    "noto_sans_arabic" => [
                        'R' => "NotoKufiArabic-Regular.ttf",
                        'B' => "NotoKufiArabic-Bold.ttf",
                    ],
                    "noto_sans_ethiopic" => [
                        'R' => "NotoKufiEthiopic-Regular.ttf",
                        'B' => "NotoKufiEthiopic-Bold.ttf",
                    ],
                ],
            ),
            'file_path' => dirname(__FILE__, 4) . '/' . $this->file_path,
        );

        $this->mpdf = new Mpdf($this->config['init_options']);
    }
    /**
     * Decides whether to create a new pdf file or use a cached one
     *
     * @return mixed: link or false
     */
    public function get_pdf() {
        if(!empty($this->pages) and !empty($this->instance)) {
            $selected = $this->check_for_existing_entry();
            if(empty($selected) or $this->modified($selected[0]->creation_date)) {
                return $this->create_pdf();
            } else {
                $path = $this->config['file_path'] . $selected[0]->pdf_name . '.pdf';
                if(file_exists($path)) {
                    return $this->get_cached_pdf($selected[0]->pdf_name);
                } else {
                    return $this->create_pdf();
                }
            }
        }
        return false;
    }

    /**
     * Creates a new pdf file for pages
     *
     * @return string: link to file
     */
    private function create_pdf() {
        // prepare content
        $pages = '';
        switch_to_blog($this->instance);
        $ite = 0;
        $count = count($this->pages);
        foreach($this->pages as &$page) {
            $post = get_post($page);

            if($ite >= $count-1) {
                $pages .= '<div class="page">';
            } else {
                $pages .= '<div class="page page-border">';
            }
            $pages .= '<h2>'.$post->post_title.'</h2>';
            $pages .= wpautop($post->post_content);
            $pages .= '</div>';
            $ite++;
        }
        switch_to_blog($this->original_instance);

        // define font family for specified language
        if(in_array($this->language, array('ar', 'fa'))) {
            $font = 'xbriyaz';
            $this->mpdf->SetDirectionality('rtl');
        } elseif(in_array($this->language, array('am', 'ti'))) {
            $font = 'noto_sans_ethiopic';
        } else {
            $font = 'noto_sans';
        }

        // content
        $out = '<html><head><style>
                    body { 
                        font-family: "'.$font.'";
                        font-size: 14px;
                        color: #000000;
                        line-height: 1.4;
                    }
                    .page {
                        margin-bottom: 35px;
                        padding-bottom: 35px;
                    }
                    .page-border {
                        border-bottom: 3px solid #BBBBBB;
                    }
                    .header_footer {
                        color: #666666;
                    }
                </style></head><body>';
        $out .= $pages;
        $out .= '</body></html>';


        // footer
        $out_footer = '<table width="100%" style="margin-top: 20px;" class="header_footer"><tr>
                        <td width="10%" valign="top" class="footer_text">{PAGENO}</td>
                        <td width="40%" valign="top" align="center">'.get_bloginfo_rss("name").'</td>
                        <td width="40%" valign="top" align="right" class="footer_text"><img 
                        src="'.IG_MPDF_PATH.'logo.png" width="auto" height="25px" /></td>
                        </tr></table>';
        $this->mpdf->setHTMLFooter($out_footer);

        // output
        $this->mpdf->WriteHTML($out);

        // save/cache pdf
        $this->cache_pdf();

        // open pdf
        return $this->get_cached_pdf();
    }

    /**
     * Cache a recently generated pdf
     */
    private function cache_pdf() {
        global $wpdb;
        $pdf_name = $this->create_file_name();

        // check if there is a corresponding table entry
        $selected = $this->check_for_existing_entry();

        if(empty($selected)) {
            // insert table entry
            $data = array();
            $data['pdf_name'] = $pdf_name;
            $data['pages'] = $this->comma_separated_list();
            $data['multiple'] = count($this->pages) > 1 ? 1 : 0;
            $data['instance'] = $this->instance;

            $wpdb->insert('wp_ig_mpdf', $data);
        } else {
            $wpdb->query("UPDATE wp_ig_mpdf SET creation_date=NOW() WHERE id=".$selected[0]->id);
        }

        // save file
        $path = $this->config['file_path'] . $pdf_name . '.pdf';
        $this->mpdf->Output($path, 'F');
    }

    /**
     * Get cached pdf link
     *
     * @param string: filename
     * @return mixed: pdf
     */
    private function get_cached_pdf($filename = null) {
        if(empty($filename)) {
			return $this->mpdf->Output();
        } else {
			header('Content-Type: application/pdf');
			echo file_get_contents(network_site_url() . $this->file_path . $filename . '.pdf');
			exit();
		}
    }

    /**
     * Create hash depending on instance and pages
     *
     * @return string mixed
     */
    private function create_hash() {
        $str = 'instance: ' . $this->instance;
        $str .= ' pages: ';
        foreach($this->pages as &$page) {
            $str .= $page . ',';
        }

        return sha1($str);
    }

    /**
     * Create file name
     *
     * @return string
     */
    private function create_file_name() {
        $out = 'Integreat-';
        $out .= $this->create_hash();
        return $out;
    }

    /**
     * Create comma separated list of pages
     *
     * @return string
     */
    private function comma_separated_list() {
        $count = count($this->pages);
        $out = '';
        for($i = 0; $i < $count; $i++) {
            $out .= $this->pages[$i];
            if($i < $count-1) {
                $out .= ',';
            }
        }

        return $out;
    }

    /**
     * Check if there exists a database table entry
     *
     * @return mixed: query result
     */
    private function check_for_existing_entry() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM wp_ig_mpdf WHERE instance='".$this->instance."' AND pages='"
            .$this->comma_separated_list()."'");
    }

    /**
     * Check if any of the pages has changed due to given timestamp
     *
     * @param string: timestamp
     * @return bool
     */
    private function modified($timestamp) {
        $date = new DateTime($timestamp);

        switch_to_blog($this->instance);
        foreach($this->pages as &$page) {
            $date_page = new DateTime(get_post_modified_time('Y-m-d H:i:s', false, $page));
            if($date_page > $date) {
                switch_to_blog($this->original_instance);
                return true;
            }
        }

        return false;
    }

    /**
     * Set articles for export
     *
     * @param pages
     */
    public function set_pages($pages) {
        foreach($pages as &$page) {
            $this->pages[] = $page;
        }
    }
}