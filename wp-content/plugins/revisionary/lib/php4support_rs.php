<?php
if( basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME']) )
	die();

if ( !function_exists('array_diff_key') ) {
	//http://us.php.net/manual/en/function.array-diff-key.php#76100
    function array_diff_key() {
		$arrs = func_get_args();
		$result = array_shift($arrs);
		foreach ($arrs as $array)
			foreach ($result as $key => $v)
				if (array_key_exists($key, $array))
					unset($result[$key]);
		
		return $result;
   }
}

if (!function_exists('array_intersect_key')) {
	//http://us.php.net/manual/en/function.array-intersect-key.php#74956
  	function array_intersect_key($isec, $keys) {
	    $argc = func_num_args();
	    if ($argc > 2) {
	      for ($i = 1; !empty($isec) && $i < $argc; $i++) {
	        $arr = func_get_arg($i);
	        foreach (array_keys($isec) as $key)
	          if (!isset($arr[$key]))
	            unset($isec[$key]);
	      }
	      
	      return $isec;
	      
	    } else {
	      $res = array();
	      foreach (array_keys($isec) as $key)
	        if (isset($keys[$key]))
	          $res[$key] = $isec[$key];
	
	      return $res;
		}
	}
}

if (! function_exists("array_fill_keys")) {
function array_fill_keys($keys, $fill_val) {
    $newarray = array();
   
	foreach($keys as $key)
		$newarray[$key] = $fill_val;
	
    return $newarray;
}
}

if (! function_exists("array_combine")) {
// http://us.php.net/manual/en/function.array-combine.php#74412
// Combines two associate arrays by making a array with the key being $a1 and the value $a2.
    function array_combine($a1,$a2) {
    	$ra = array();
    	
    	reset($a2);
 
    	foreach ( array_keys($a1) as $a1_key ) {
    		$ra[ $a1_key ] = $a2[ key($a2) ];
    		next( $a2 );
    	}

        if(isset($ra)) return $ra; else return false;
    }
}

if (! function_exists("htmlspecialchars_decode")) {
// http://us.php.net/manual/en/function.htmlspecialchars-decode.php#82133
// thomas at xci[ignore_this]teit dot commm
    function htmlspecialchars_decode($string,$style=ENT_COMPAT)
    {
        $translation = array_flip(get_html_translation_table(HTML_SPECIALCHARS,$style));
        if($style === ENT_QUOTES){ $translation['&#039;'] = '\''; }
        return strtr($string,$translation);
    }
}

?>