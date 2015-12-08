<?php
//--- extend TCPDF class ---------------------------------------

class SSA_PDF extends TCPDF
{

	var $bg_rgb = array(
		'red' 	=> 255,
		'green' => 255,
		'blue' 	=> 255 
	);

	var $text_font = 'helvetica';
	var $text_hex = '#363636';
	var $link_hex = '#3333ff';

	var $text_rgb = array(
		'red' 	=> 40,
		'green' => 40,
		'blue' 	=> 40 
	);
	
	var $link_rgb = array(
		'red' 	=> 40,
		'green' => 40,
		'blue' 	=> 255 
	);
		
	var $displayDate = '';
	var $siteURL = '';
	var $siteTitle = '';
	
	
	//--- custom header
	public function Header()
	{
		//set the bg colour
		$bMargin = $this->getBreakMargin(); 		//get the current page break margin
		$auto_page_break = $this->AutoPageBreak; 	//get current auto-page-break mode
		$this->SetAutoPageBreak(false, 0); 			//disable auto-page-break

		/*####################################################
		#                                                    #
		#			    Modified Header BEGIN                #
		#                                                    #
		#/*##################################################*/
		$rightshift = 0;
		$leftshift = 0;

		/**
		* if RTL is true, the header style is messed up --> work around
		* the function header will be called within AddPage method of TCPDF
		* (thus the we get the proper RTL value)
		*/
		if ($this->getRTL()){
			$rightshift = 52;
			$leftshift = 23;
		}

		$image_file = __FILE__; //...wordpress\wp-content\plugins\pdf-creator-lite\scripts\extend-class-tcpdf.php
		$image_file = str_replace('\\','/',$image_file);
		$image_file = dirname($image_file);	//...wordpress/wp-content/plugins/pdf-creator-lite/scripts/
		$image_file = dirname($image_file); //...wordpress/wp-content/plugins/pdf-creator-lite
		$image_file = $image_file.'/images/integreat_trans_logo.jpg';
        $this->Image($image_file,
				18 + $rightshift,
				5,
				0,
				15,
				'JPG',
				'',
				'T',
				false,
				300,
				'',
				false,
				false,
				0,
				false,
				false,
				false
			);
		// Set font
		$headerContent = '<br><p style="font-family:' . $this->text_font . '; font-size:10px; color:' . $this->text_hex . '; line-height:15px;">Page: '. $this->siteTitle . ' - Date: ' . $this->displayDate .'</p>';
        // Title

		$this->writeHTMLCell(
			0,
			25,
			$leftshift,
			6,
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

		/*####################################################
		#                                                    #
		#			     Modified Header END                 #
		#                                                    #
		#/*##################################################*/

		/*
		$this->Rect	(
			0,
			0,
			310,
			297,
			'F',
			array(),
			array( $this->bg_rgb['red'], $this->bg_rgb['green'], $this->bg_rgb['blue'] )
		);

		$this->SetAutoPageBreak( $auto_page_break, $bMargin ); 	//restore auto-page-break status
		$this->setPageMark(); 									//set the starting point for the page content

		//make header content
		$headerContent = '<p style="font-family:' . $this->text_font . '; font-size:14px; color:' . $this->text_hex . '; line-height:20px;">' . $this->siteTitle . ';<span style="font-size:11px;">- ' . $this->displayDate . '<br />View online at <a href="' . $this->siteURL . '" style="color:' . $this->link_hex . '; text-decoration:none;">' . $this->siteURL . '</a></span></p>';
		$this->writeHTMLCell(
			0,
			3,
			PDF_MARGIN_LEFT,
			5,
			$headerContent,
			0,
			2,
			false,
			true,
			'L',
			false
		);

		$style = array( 'width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'phase' => 1, 'color' => array($this->text_rgb['red'], $this->text_rgb['green'], $this->text_rgb['blue']) );
		$this->Line( 20, 27, 188, 27, $style );
		*/
	}
	
	
	//--- custom footer
	public function Footer ()
	{
		$this->SetY( -15 ); //position 15 mm from bottom		
		$this->SetFont( $this->text_font , 'N', 8 );

		$this->SetTextColorArray	(
			array( $this->text_rgb['red'], $this->text_rgb['green'], $this->text_rgb['blue'] ),
			false
		);
		
		$this->Cell( 173, 10, 'Page '.$this->getAliasNumPage(), 0, false, 'R', 0, '', 0, false, 'T', 'M' );
		
		$style = array( 'width' => 0.25, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'phase' => 1, 'color' => array($this->text_rgb['red'], $this->text_rgb['green'], $this->text_rgb['blue']) );
		$this->Line(20, 282, 188, 282, $style);
	}
	
} //close class SSA_PDF

?>