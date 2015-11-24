<?php
class ICanLocalizeQuery{
      private $site_id; 
      private $access_key;
      private $error = null;

      function __construct($site_id=null, $access_key=null){             
            $this->site_id = $site_id;
            $this->access_key = $access_key;
      } 
      
      public function setting($setting){
          return $this->$setting;
      }
      
      public function error(){
          return $this->error;
      }

    function updateAccount($data){        
        $request = ICL_API_ENDPOINT . '/websites/'.$data['site_id'].'/update_by_cms.xml';
        unset($data['site_id']);
        $response = $this->_request($request, 'POST', $data);        
        if(!$response){
            return $this->error;
        }else{
            return 0;            
        }
    }

	/**
	 * @return array of website details returned from a direct API call to ICL
	 */
	function get_website_details() {
		global $sitepress;

		$website_details_cache_index = '_last_valid_icl_website_details';
		$request_url                 = ICL_API_ENDPOINT . '/websites/' . $this->site_id . '.xml?accesskey=' . $this->access_key;
		$res                         = $this->_request( $request_url );
		if ( isset( $res['info']['website'] ) ) {
			$res = $res['info']['website'];
			$sitepress->set_setting( $website_details_cache_index, $res, true );
		} else {
			$res = $sitepress->get_setting( $website_details_cache_index, array() );
		}

		return $res;
	}
    
    function _request($request, $method='GET', $formvars=null, $formfiles=null, $gzipped = false){
        global $sitepress_settings;
        $request = str_replace(" ", "%20", $request);
        $c = new IcanSnoopy();
        
        $debugvars =  array(
            'debug_cms' => 'WordPress',
            'debug_module' => 'WPML ' . ICL_SITEPRESS_VERSION,
            'debug_url'     => get_bloginfo('url')
        );
        
        if($method == 'GET'){
            $request .= '&' . http_build_query($debugvars);    
        }else{
            $formvars += $debugvars;
        }
        
        // disable error reporting
        // needed for open_basedir restrictions (is_readable)
        $_display_errors = ini_get('display_errors');
        $_error_reporting = ini_get('error_reporting');
        ini_set('display_errors', '0');        
        ini_set('error_reporting', 0);        
        
        if (!@is_readable($c->curl_path) || !@is_executable($c->curl_path)){
            $c->curl_path = '/usr/bin/curl';
        }        
        
        // restore error reporting
        // needed for open_basedir restrictions
        ini_set('display_errors', $_display_errors);        
        ini_set('error_reporting', $_error_reporting);
        
        $c->_fp_timeout  = 3;
        $c->read_timeout = 5;
        if ( $sitepress_settings['troubleshooting_options']['http_communication'] ) {
            $request = str_replace( 'https://', 'http://', $request );
        }
        if ( $method == 'GET' ) {
            $c->fetch( $request );
        } else {
            $c->set_submit_multipart();
            $c->submit( $request, $formvars, $formfiles );
        }

        if ( $c->error || $c->timed_out ) {
            $this->error = $c->error;

            return false;
        }
        
        if($gzipped){
            $c->results = $this->_gzdecode($c->results);
        }        
        $results = icl_xml2array($c->results,1);                        
        
        if(isset($results['info']) && $results['info']['status']['attr']['err_code']=='-1'){
            $this->error = $results['info']['status']['value'];            
            return false;
        }
                
        return $results;
    }
    
    function _gzdecode($data){
        
        return icl_gzdecode($data);
    }
    
    function cms_create_message($body, $from_language, $to_language){
        $request_url = ICL_API_ENDPOINT . '/websites/'. $this->site_id . '/create_message.xml';    
        $parameters['accesskey'] = $this->access_key;
        $parameters['body'] = base64_encode($body);
        $parameters['from_language'] = $from_language;
        $parameters['to_language'] = $to_language;
        $parameters['signature'] = md5($body.$from_language.$to_language);
        $res = $this->_request($request_url, 'POST' , $parameters);        
        if($res['info']['status']['attr']['err_code']=='0'){
            return $res['info']['result']['attr']['id'];
        }else{
            return isset($res['info']['status']['attr']['err_code'])?-1*$res['info']['status']['attr']['err_code']:0;
        }
    }
}
  
/**
 * gzdecode implementation
 *
 * @see http://hu.php.net/manual/en/function.gzencode.php#44470
 * 
 * @param string $data
 * @param string $filename
 * @param string $error
 * @param int $maxlength
 * @return string
 */
function icl_gzdecode($data, &$filename = '', &$error = '', $maxlength = null) {
    $len = strlen ( $data );
    if ($len < 18 || strcmp ( substr ( $data, 0, 2 ), "\x1f\x8b" )) {
        $error = "Not in GZIP format.";
        return null; // Not GZIP format (See RFC 1952)
    }
    $method = ord ( substr ( $data, 2, 1 ) ); // Compression method
    $flags = ord ( substr ( $data, 3, 1 ) ); // Flags
    if ($flags & 31 != $flags) {
        $error = "Reserved bits not allowed.";
        return null;
    }
    $headerlen = 10;
    if ($flags & 4) {
        // 2-byte length prefixed EXTRA data in header
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $extralen = unpack ( "v", substr ( $data, 8, 2 ) );
        $extralen = $extralen [1];
        if ($len - $headerlen - 2 - $extralen < 8) {
            return false; // invalid
        }
        $headerlen += 2 + $extralen;
    }
    $filename = "";
    if ($flags & 8) {
        // C-style string
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $filenamelen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
        if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
            return false; // invalid
        }
        $filename = substr ( $data, $headerlen, $filenamelen );
        $headerlen += $filenamelen + 1;
    }
    if ($flags & 16) {
        // C-style string COMMENT data in header
        if ($len - $headerlen - 1 < 8) {
            return false; // invalid
        }
        $commentlen = strpos ( substr ( $data, $headerlen ), chr ( 0 ) );
        if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
            return false; // Invalid header format
        }
        $headerlen += $commentlen + 1;
    }
    if ($flags & 2) {
        // 2-bytes (lowest order) of CRC32 on header present
        if ($len - $headerlen - 2 < 8) {
            return false; // invalid
        }
        $calccrc = crc32 ( substr ( $data, 0, $headerlen ) ) & 0xffff;
        $headercrc = unpack ( "v", substr ( $data, $headerlen, 2 ) );
        $headercrc = $headercrc [1];
        if ($headercrc != $calccrc) {
            $error = "Header checksum failed.";
            return false; // Bad header CRC
        }
        $headerlen += 2;
    }
    // GZIP FOOTER
    $datacrc = unpack ( "V", substr ( $data, - 8, 4 ) );
    $datacrc = sprintf ( '%u', $datacrc [1] & 0xFFFFFFFF );
    $isize = unpack ( "V", substr ( $data, - 4 ) );
    $isize = $isize [1];
    // decompression:
    $bodylen = $len - $headerlen - 8;
    if ($bodylen < 1) {
        // IMPLEMENTATION BUG!
        return null;
    }
    $body = substr ( $data, $headerlen, $bodylen );
    $data = "";
    if ($bodylen > 0) {
        switch ($method) {
            case 8 :
                // Currently the only supported compression method:
                $data = gzinflate ( $body, $maxlength );
                break;
            default :
                $error = "Unknown compression method.";
                return false;
        }
    } // zero-byte body content is allowed
    // Verifiy CRC32
    $crc = sprintf ( "%u", crc32 ( $data ) );
    $crcOK = $crc == $datacrc;
    $lenOK = $isize == strlen ( $data );
    if (! $lenOK || ! $crcOK) {
        $error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
        return false;
    }
    return $data;
}
