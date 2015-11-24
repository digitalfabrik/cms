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

	
	II VERSIONs
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