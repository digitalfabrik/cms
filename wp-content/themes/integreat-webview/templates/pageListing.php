<?php if($iterator%2 == 0): ?>
    <div class="pagesWrap clearfix">
<?php endif; ?>
<div class="page col-xs-12 col-sm-6">
    <a href="<?php the_permalink(); ?>">
        <div class="thumb">
            <div>
                <?php echo wp_get_attachment_image( get_post_thumbnail_id(), 'thumb' ); ?>
            </div>
        </div>
        <div class="cont">
            <?php if( get_post_type() == 'event' ): ?>
                <small><?php _e('Event','integreat'); ?></small>
            <?php endif; ?>
            <h3><?php the_title(); ?></h3>
            <div class="text">
                <span class="hidden-sm hidden-md hidden-lg"><?php echo wp_trim_words( strip_tags($post->post_content), 10, ' [...]' ); ?></span>
                <span class="hidden-xs hidden-md hidden-lg"><?php echo wp_trim_words( strip_tags($post->post_content), 20, ' [...]' ); ?></span>
                <span class="hidden-xs hidden-sm"><?php echo wp_trim_words( strip_tags($post->post_content), 30, ' [...]' ); ?></span>
            </div>
        </div>
    </a>
</div>
<?php if($iterator%2 == 1): ?>
    </div>
<?php endif; ?>
<?php $iterator++; ?>