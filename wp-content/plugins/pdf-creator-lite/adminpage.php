<?php
/**
 *	Writes the admin page
 */
function SSAPDF_drawAdminPage ()
{
	$blogID = get_current_blog_id();
	?>
		
	<style type="text/css">
		.tabbuttons-wrap {
			position:relative; border-bottom:1px solid #ccc; height:30px; padding:5px 0 0 0; width:auto; max-width:800px; overflow:visible;
		}
		.tabbutton { 
			float:left; 
			padding:6px 18px 6px 18px; 
			font-size:11px; margin:0 2px 0 0;
			background:#ddd; 
			font-weight:700;
			color:#777;
			-webkit-border-top-left-radius: 3px;
			-webkit-border-top-right-radius: 3px;
			-moz-border-radius-topleft: 3px;
			-moz-border-radius-topright: 3px;
			border-top-left-radius: 3px;
			border-top-right-radius: 3px;
			border-bottom:1px solid #ccc;
			cursor:pointer;
		}
		.active-tab {
			background:#f0f0f0;
			border:1px solid #ccc;
			border-bottom:0px;
			padding:5px 17px 7px 17px;
			color:#444;
		}

		.tabs-wrap { position:relative; height:auto; border-bottom:1px solid #bbb; max-width:800px; }
		.ssapdf-tab { position:relative; height:auto; padding:20px 0px 40px 12px;  }

		#ssapdf_tab_1 { display:none; }
		#ssapdf_tab_2 { display:none; }
		#ssapdf_tab_3 { display:none; }	
		
		
		#pdfShortcodeTable td{border-bottom:1px solid #ccc;}
		#pdfShortcodeTable th{background:#bbb; color:#fff; text-align:left; padding:5px 10px 5px 2px}
		
		#exampleShortcode
		{
			padding:5px; background:#fff; border:solid #333333 1px;
		}
		
		<?php
		echo '.waitingDiv { padding:0 0 0 30px; height:20px; background:url('.SSAPDF_PLUGIN_URL.'/images/loader.gif) no-repeat; } ';
		?>
	</style>

	
	<div class="wrap">		
		<h2>PDF Creator &nbsp;<span style="font-size:8px;">v 1.2</span></h2>
		
		<!--
		<br /><div id="feedback"></div>
		<h3 id="atest" onclick="testAdminAjax('arf')">&raquo; test ajax click</h3>
		-->
		
		
	<?php
	//if ( isset($_POST['formSubmit']) )
	//{		
	//	SSAPDF_generatePDF();
	//	SSAPDF_drawAdminFeedback();
	//}
	//else
	//{
	
	
		echo '<div id="interfaceWrap">';
		
		
		echo '<p>This will export all or some of the pages in this site as a PDF file.<br />Use the options below to choose how your PDF will be created.</p><br />';
		echo '<form method="post" action="' . $_SERVER["REQUEST_URI"] . '">';
		?>
		
		<div class="tabbuttons-wrap">
			<div class="tabbutton" id="ssapdf_tabbutton_0">Content</div>
			<div class="tabbutton" id="ssapdf_tabbutton_1">Format</div>
			<div class="tabbutton" id="ssapdf_tabbutton_2">Frontend Shortcodes</div>
			<br class="clearB" />
		</div>
		<div class="tabs-wrap">
			
		<!-- TAB 0 - CONTENT.......................... -->
			<div class="ssapdf-tab" id="ssapdf_tab_0">
        
			<?php
			
			//echo '<h3>Select your content</h3>';
			//echo '<input type="radio" name="contentType" id="selectPages" value="selectPages" checked onclick="javascript:toggleContentDiv(this.value)">';
			//echo '<label for="selectPages">Pages</label><br/>';
			
			//echo '<input type="radio" name="contentType" id="selectPosts" value="selectPosts" onclick="javascript:toggleContentDiv(this.value)">';
			//echo '<label for="selectPosts">Posts</label><br/>';			
			
			//echo '<input type="radio" name="contentType" id="selectCustom" value="selectCustom" onclick="javascript:toggleContentDiv(this.value)">';			
			//echo '<label for="selectCustom">Custom Posts</label><br/>';
			
			
			echo '<div id="selectPagesDiv">';		
			echo '<h3>Select your pages</h3>';	
			$parents = array();
		
			$args = array(
				'sort_order' => 'ASC',
				'sort_column' => 'menu_order',
				'hierarchical' => 1,
				'exclude' => '',
				'include' => '',
				'post_type' => 'page',
				'post_status' => 'publish'
			);
			$pages = get_pages( $args );
			
			echo '<div style="height:30px; border-bottom:1px solid #ccc;">';
			echo '<input type="checkbox" value="true" id="checkAll" name="checkAll" checked="checked" /><label for="checkAll">Uncheck / Check All</label>';
			echo '</div><br />';
			
			if ( !empty($pages) && $pages != false )
			{
				foreach ($pages as $page)
				{
					$depth = SSAPDF_getPageDepth( $page->ID );
					$fontWeight = ($depth == 0) ? '700' : '500';
					if ( $depth == 0 )
					{
						$checkerClass = ' page' . $page->ID;
						$addCheckerClass = false;
						$parents[] = $page->ID;
					}
					else
					{
						$addCheckerClass = true;
					}
					echo '<p style="font-weight:' . $fontWeight . '; margin:0 0 4px ' . ($depth > 0 ? 2*$depth : '') . '0px;"><input type="checkbox" name="checkerPage[' . $page->ID . ']" class="checkerPage' . ($addCheckerClass === true ? $checkerClass : '') . '" value="' . $page->ID . '" checked="checked" id="page' . $page->ID . '" /><label for="page' . $page->ID . '">' . $page->post_title . '</label></p>';
				}
			}
			echo '</div>';
			
			
			// POSTS
			echo '<div id="selectPostsDiv" style="display:none">';
			echo '<h3>Select your posts</h3>';
			
			echo '<input type="checkbox" id="allPosts" value="all" checked>';
			echo '<label for="allPosts">All Posts</label><hr/>';
			echo '<b>OR</b><br/>select from Categories and Tags<br/>';
			
			
			echo '<div style="float:left; width=300px; padding-right:50px">';
			echo '<h4>Categories</h4>';
			
			echo 'TO DO: add indents for sub cats<br/>';
			
			$args = array(
			'hide_empty' => false

			);
			$categories = get_categories($args);
			foreach($categories as $category)
			{ 
				$catName = $category->name;
				$catCount = $category->count;
				$catID = $category->cat_ID;
				$catParent = $category->category_parent;
				

				
				echo '<input type="checkbox" value="catID_'.$catID.'" id="catID_'.$catID.'">';
				echo '<label for="catID_'.$catID.'">'.$catName.' ('.$catCount.')</label><br/>';

			}
			echo '</div>';
			echo '<div style="float:left; width=300px; padding-right:30px">';
			echo '<h4>Tags</h4>';
			
			$args = array(
			'hide_empty' => false
			);
			$tags = get_tags($args);
			foreach ( $tags as $tag )
			{
				$tagID = $tag->term_id;
				$tagName = $tag->name;	
				
				echo '<input type="checkbox" value="tagID_'.$tagID.'" id="tagID_'.$tagID.'">';
				echo '<label for="tagID_'.$tagID.'">'.$tagName.'</label><br/>';
				
			}			
						

			echo '</div>';
			echo '<div style="clear:both"></div>';

			
			
				
			echo '</div>';
			
			// Custom Posts
			echo '<div id="selectCustomDiv" style="display:none">';		
			echo '<h3>Select your custom post types</h3>';
			
			$args = array(
			   'public'   => true,
			   '_builtin' => false
			);
			
			$output = 'names'; // names or objects, note names is the default
			$operator = 'and'; // 'and' or 'or'
			
			$post_types = get_post_types( $args, $output, $operator ); 
			
			foreach ( $post_types  as $post_type )
			{
				echo '<input type="checkbox" value="custom'.$post_type.'" id="custom_'.$post_type.'">';
				echo '<label for="custom_'.$post_type.'">'.$post_type.'</label><br/>';
			}		
			
			
			echo '</div>';
			
			
			
			
			
			?>    
            </div>    

			
			
		<!-- TAB 1 - FORMAT.......................... -->			
			<div class="ssapdf-tab" id="ssapdf_tab_1">
            
				<?php
				
				echo '<h3>Insert Options</h3>';
				echo '<p><input type="checkbox" name="addFrontPage" id="addFrontPage" style="margin-left:10px;" /> <label for="addFrontPage">Add a Front Page.</label></p>';
				echo '<p><input type="checkbox" name="addToC" id="addToC" style="margin-left:10px;" /> <label for="addToC">Add a Table of Contents.</label></p>';
				
				
				echo '<br /><h3>Style Options</h3>';
				echo '<style type="text/css"> ';
				echo 	'table.pdf td { vertical-align:top; padding:10px; } ';
				echo '</style>';		
				
				echo '<table class="pdf"><tbody>';
				
				echo 	'<tr>';
				echo 		'<td style="width180px;"><input type="radio" value="none" name="useCSS" id="radioNone" checked="checked" /> <label for="radioNone">Default Style</label></td>';
				echo 		'<td><span class="description" style="color:#aaa;"></span></td>';
				echo 	'</tr>';		
				
				echo 	'<tr>';
				echo 		'<td><input type="radio" value="custom" name="useCSS" id="radioCustom" /> <label for="radioCustom">Customise Style</label></td>';
				echo 		'<td>';
				echo			'<span class="description" style="color:#aaa;">&nbsp;&nbsp; Use these settings to customise your PDF.</span>';
				echo 			'<div style="padding:15px 0px 0px 10px;">';
				
				echo				'<div style="float:left; width:100px;">Font:</div>';
				echo				'<input type="text" value="#333" name="font_cpicker" id="font_cpicker"/>';
				echo 				'&nbsp; &nbsp;<select name="fontFamily" id="fontFamily">';
				echo 					'<option value="helvetica">Arial / Helvetica</option>';
				echo 					'<option value="times">Times New Roman</option>';
				echo 					'<option value="courier">Courier</option>';
				do_action('fox_add_fonts');
				echo 				'</select>';
				echo				'<br clear="left" /><br />';
				
				echo				'<div style="float:left; width:100px;">Background:</div>';
				echo				'<input type="text" value="#fff" name="bg_cpicker" id="bg_cpicker"/>';
				echo				'<br clear="left" /><br />';
				
				echo				'<div style="float:left; width:100px;">Links:</div>';
				echo				'<input type="text" value="#4848ff" name="link_cpicker" id="link_cpicker"/>';
				echo				'<br clear="left" />';
				
				echo 			'<div>';
				echo 		'</td>';
				echo 	'</tr>';		
				
				echo '</tbody></table>';
				
				
				
				?>            
            
			</div>
			
			
		<!-- TAB 2 - SHORTCODES.......................... -->	
			<div class="ssapdf-tab" id="ssapdf_tab_2">
				Use the shortcode:
                <div id="exampleShortcode" style="width:90px; text-align:center">[pdf-lite]</div>on any page to add a download link for front end users. If no paramaters are given it will download the current page<hr/>
				<b>Shortcode Optional Parameters</b><br/>
				<table id="pdfShortcodeTable">
					<tr><th>Parameter</th><th>Example</th><th>Description</th></tr>
					<tr><td>toc</td><td>toc="true"</td><td>Add a table of contents</td></tr>
					<tr><td>titlepage</td><td>titlepage="Your Title Page Text"</td><td>Add a Title Page of your choice</td></tr>
					<tr><td>linktext</td><td>linktext="Download this page as a PDF"</td><td>Change the link text. Default is 'Download PDF'</td></tr>
					<tr><td>allpages</td><td>allpages="true"</td><td>This will download all pages in the blog</td></tr>
					<tr><td>allposts</td><td>allposts="true"</td><td>This will download all posts in the blog</td></tr>
					<tr><td>cat</td><td>cat="5, food, animals"</td><td>This will download posts in the specified categories</td></tr>
					<tr><td>page</td><td>page="2, pageslug1"</td><td>This will download pages with given IDs or page slugs</td></tr>
					<tr><td>icon</td><td>icon="2"</td><td>Changes the pdf download icon. use "false" for no icon</td></tr>
					<tr><td>iconsize</td><td>iconsize="25"</td><td>The size in pixels of the icon. The default size is 64 px</td></tr>
					<tr><td>filename</td><td>filename="my-download"</td><td>The filename WTHOUT .pdf extension</td></tr> 
					<tr><td>font</td><td>font="times"</td><td>Values can be times, courier, or helvetica</td></tr>
					<tr><td>fontcolor</td><td>fontcolor="#03f"</td><td>Any valid hex colour value</td></tr>
					<tr><td>linkcolor</td><td>linkcolor="#03f"</td><td>Any valid hex colour value</td></tr>
					<tr><td>bgcolor</td><td>bgcolor="#03f"</td><td>Any valid hex colour value</td></tr>
				</table>
				
				<h4>Example Shortcodes</h4>
				This will create a PDF of the current page
				<div id="exampleShortcode">[pdf-lite]</div>
				<hr/>
				This will create a title page called 'My Book', add a table of contents and have no PDF icon next to the download link
				<div id="exampleShortcode">[pdf-lite titlepage="My Book" toc="true" icon="false"]</div>
				<hr/>
				This will create a PDF of the pages with ID 5 and the pages with the slugs 'about-me' and 'about-my-job'            
				 <div id="exampleShortcode">[pdf-lite page="5, about-me, about-my-job"]</div>
				 <hr/>
				 
				 <h4>Available Shortcode Icons</h4>
				
				<?php
				$iconArray = getPDFIconArray();		
				$pdfIconDir = SSAPDF_PLUGIN_URL.'/images/pdf_icons/';
				
				echo '<table id="pdfShortcodeTable">';
				$i=1;
				$v=1;
				foreach($iconArray as $myIcon)
				{
					if($i==1){echo '<tr>';}
					echo '<td align="center" style="padding:25px">';
					echo '<img src="'.$pdfIconDir.$myIcon.'">';
					echo '<br/><span style="font-size:18px">icon="'.$v.'"</span><br/>';
					echo '</td>';
					$i++;
					$v++; // incremebt the icon name
					if($i>=4){$i=1; echo '</tr>';}
				}
				if($i<>1){echo '</tr>';}
				echo '</table>';
				?>
            </div>            
				

				
			</div>        
            

		
		<?php						
		echo '<br /><br />';
		//echo '<div style="float:left; width:120px; height:50px; padding-top:5px;"><input type="submit" value="Create PDF" name="formSubmit" class="button-primary" /></div>';
		echo '<div style="float:left; width:120px; height:50px; padding-top:5px;"><span class="button-primary" id="makeAdminPdf" onclick="JSadminBuildPDF()">Create PDF</span></div>';
		echo '<p style="float:left; margin:0;">For large sites it may take a minute<br />or two to complete the conversion!</p>';
		
		echo '</form>';
		
				
		
		echo '</div>'; //close #interfaceWrap
		
		?>
		
		<script src="<?php echo SSAPDF_PLUGIN_URL; ?>/colourpicker/spectrum.js"></script>
		
		<script type="text/javascript">
			
			function checkCustomRadio () {
				jQuery('#radioCustom').attr('checked', true);
			};
			
			var SSA_ADMIN = {
		
				last_tab: 0,
				
				add_tab_listener: function ( j ) {
					var that = this;
					jQuery('#ssapdf_tabbutton_' + j).click( function (e) {
						if ( j !== that.last_tab ) {
							jQuery('#ssapdf_tab_' + that.last_tab).hide();
							jQuery('#ssapdf_tabbutton_' + that.last_tab).removeClass('active-tab');
							jQuery('#ssapdf_tab_' + j).show();
							jQuery('#ssapdf_tabbutton_' + j).addClass('active-tab');
							that.last_tab = j;
						}
					});
				},
				
				init: function () {
					var j;
					for ( j = 0; j < 3; j += 1 ) {
						this.add_tab_listener( j );
					}
					jQuery('#ssapdf_tabbutton_' + this.last_tab).addClass('active-tab');
				}
			};
				
			
			jQuery(document).ready( function () {
			
				jQuery('#bg_cpicker').spectrum({
					color: "#fff",
					clickoutFiresChange: true,
					change: function(color) {
						checkCustomRadio();
					}
				});
				jQuery('#font_cpicker').spectrum({
					color: "#333",
					clickoutFiresChange: true,
					change: function(color) {
						checkCustomRadio();
					}
				});
				jQuery('#link_cpicker').spectrum({
					color: "#4848ff",
					clickoutFiresChange: true,
					change: function(color) {
						checkCustomRadio();
					}
				});
				
				jQuery('#fontFamily').on( 'change', function ( e ) {
					checkCustomRadio();
				});
				
				jQuery('#checkAll').on( 'change', function ( e ) {
					var checked = jQuery(this).is(':checked');
					jQuery( '.checkerPage' ).prop('checked', checked);
				});

		<?php
		if ( ! empty( $parents ) )
		{
			$selector = '';
			$c = count( $parents );
			foreach ( $parents as $i => $pageID )
			{
				$selector .= '#page' . $pageID . ($i < ($c-1) ? ', ' : ''); 
			}
		?>
					jQuery('<?php echo $selector; ?>').on( 'change', function ( e ) {
						var sel = jQuery(this).attr('id');	
						var checked = jQuery(this).is(':checked');
						jQuery( '.' + sel ).prop('checked', checked);
					});
		<?php
		}
		?>			
				SSA_ADMIN.init();
				
			});
	
	
	
	
	
	function toggleContentDiv(currentContent)
	{

		var pagesDiv = document.getElementById('selectPagesDiv');
		var postsDiv = document.getElementById('selectPostsDiv');	
		var customDiv = document.getElementById('selectCustomDiv');				

		if(currentContent=="selectPosts")
		{
			pagesDiv.style.display = 'none';
			postsDiv.style.display = 'block';
			customDiv.style.display = 'none';						
		}
		if(currentContent=="selectPages")
		{
			pagesDiv.style.display = 'block';
			postsDiv.style.display = 'none';
			customDiv.style.display = 'none';						
		}
		if(currentContent=="selectCustom")
		{
			pagesDiv.style.display = 'none';
			postsDiv.style.display = 'none';
			customDiv.style.display = 'block';						
		}				


	}
	
	
	</script>
		
	</div>
    
	<?php
	//}
}

?>