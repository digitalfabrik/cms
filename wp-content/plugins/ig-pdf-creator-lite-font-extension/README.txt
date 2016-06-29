	I GENERAL
	#########


1) Version-trouble
==================
This plugin makes use of the pdf-creator-lite plugin. Due to missing hooks, the pdf-creator-lite plugin has slightly been modified.
More particularly, one filter hook was added:

apply_filters('fox_modify_pdf',$pdf); 

within the php-file

"build-pdf-admin.php".

It was added at line 103 (at time), which is right after all pdf settings have been adjusted (e.G.: "$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);").

Furthermore , one action hook was added:

do_action('fox_add_fonts');

withing the php-file

"adminpage.php"

It was addead at line 276 (at time), which is withing the font selection tag ("<select name="fontFamily" id="fontFamily">")

2. Install a new font
=====================
A new font can be installed by just adding the .ttf file to the fonts folder of the pdf-creator-lite-font-extension plugin.
NOTE: The filename of the .ttf file might cause trouble. Make sure it has no '-' chars in it. (TCPDF modifies the filename than, which causes the trouble)

3. Support
==========

In case anything is not working as it should, contact p.blanz@tum.de

	
	II VERSIONS
	###########

1. Version 1.0
==============

+ Allows to add new fonts as explained above
+ Added two hooks to the plugin core as explained above


2. Version 1.1
==============

+ Added two fonts:
	"aerufat" for ArabiC
	"dejavusans" for Persian
+ The font will be automatically selected according to the current language (ICL_LANGUAGE_CODE)
+ removed the selected="selected" attribute from the font selection to guarantee automatic language selection
+ minor bugfixes

3. Version 1.2
==============

+ Added branding of the PDFs (Integreat Logo is displayed now, header slightly modified)
+ Modified the Header() method of the build-pdf-admin.php script:

	function Header(){
		$bMargin = $this->getBreakMargin(); 		//get the current page break margin
		$auto_page_break = $this->AutoPageBreak; 	//get current auto-page-break mode
		$this->SetAutoPageBreak(false, 0); 			//disable auto-page-break
		$path = __FILE__;							// --> ...\wordpress\wp-content\plugins\pdf-creator-lite\scripts\extend-class-tcpdf.php
		$path = str_replace('\\','/',$path);
		$path = strtolower($path);
		$path = dirname($path);						// --> .../wordpress/wp-content/plugins/pdf-creator-lite/scripts/
		$path = dirname($path);						// --> .../wordpress/wp-content/plugins/pdf-creator-lite
		if(strcasecmp(basename($path),'pdf-creator-lite')!=0){		//wrong path
			error_log(print_r('WRONG PATH:'.$path, true));
			return;
		}
		$left_shift = 0;
		if($this->getRTL()){						//adjust style
			$left_shift = 50;
		}
		$image = $path.'/images/Integreat_Logo.jpg';
		
		$this->Image($image, 18 + $left_shift, 3, 50, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);


		
		$this->SetAutoPageBreak( $auto_page_break, $bMargin ); 	//restore auto-page-break status
		$this->setPageMark(); 									//set the starting point for the page content		
		
		$headerContent = '<p style="font-family:' . $this->text_font . '; font-size:14px; color:' . $this->text_hex . '; line-height:20px;">' . $this->siteTitle . ' - ' . $this->displayDate .'</p>';
		
		$this->writeHTMLCell(
			0,
			0,
			PDF_MARGIN_LEFT,
			10,
			$headerContent,
			0,
			2,
			false,
			true,
			'R',
			false
		);
		
		$style = array( 'width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'phase' => 1, 'color' => array($this->text_rgb['red'], $this->text_rgb['green'], $this->text_rgb['blue']) );
		$this->Line( 20, 23, 188, 23, $style );
	}