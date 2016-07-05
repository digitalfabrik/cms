    <section id="bottom" class="footer-wiget-area">
        <div class="container">
            <div class="row">
               <?php dynamic_sidebar('bottom'); ?>
            </div>
        </div>
    </section>
    <?php global $themeum; ?>
    <footer id="footer" class="midnight-blue">
        <div class="container">
            <div class="row">
                <div class="col-sm-6">
                  <?php if(isset($themeum['copyright_text'])) echo $themeum['copyright_text']; ?>
                </div>
                <div class="col-sm-6">
                    <?php if(has_nav_menu('secondary')): ?>
                        <?php wp_nav_menu( array( 'theme_location' => 'secondary', 'container'  => false, 'menu_class' => 'footer-menu pull-right','depth' => 1 ) ); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <a id="gototop" class="gototop" href="#"><i class="icon-chevron-up"></i></a><!--#gototop-->
    </footer><!--/#footer-->
</div>
<?php if(isset($themeum['before_body']))  echo $themeum['before_body']; ?>
<?php if(isset($smof_data['google_analytics'])) echo $smof_data['google_analytics'];?>

    <?php if(isset($smof_data['custom_css'])): ?>
        <?php if(!empty($smof_data['custom_css'])): ?>
            <style>
                <?php echo $smof_data['custom_css']; ?>
            </style>
        <?php endif; ?>
    <?php endif; ?>
<?php wp_footer(); ?>

</body>
</html>