<div id="linkPDF">
    <a onclick="$('#createPDF').css('height', '100%');"><img src="<?php bloginfo('template_url'); ?>/images/pdf.png" /></a>
</div>

<div id="createPDF">
    <div style="width: 70%; float:right; border: 1px;">
    <div id="createPDFClose" style="right:0; text-align: right; color: #ffffff;"><a onclick="$('#createPDF').css('height', '0%');"><h1 style='margin-right:20px;'>Close</h1></a></div>
    <?php
        if(is_front_page()) {
            echo(drawFrontEndPDF_download(array('allpages'=>true)));
        }
        else {
            $args = array(
                'post_parent' => get_the_ID(),
                'post_type'   => 'page', 
                'numberposts' => -1,
                'post_status' => 'publish' 
            );
            $children = get_children( $args );
            $var = "";
            foreach($children as $child) { $var .= $child -> ID.", "; }
            $atts['page'] = $var;
            var_dump($atts);
            echo(drawFrontEndPDF_download($atts));
        }
    ?>
    </div>
</div>
