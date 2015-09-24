<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        
        <?php $slides = rwmb_meta('thm_gallery_images','type=image_advanced'); ?>
        <?php $count = count($slides); ?>
        <?php if($count > 0): ?>
            <div id="blog-gallery-slider" class="carousel slide" data-ride="carousel">

                <!-- Wrapper for slides -->
                <div class="carousel-inner">

                    <?php $slide_no = 1; ?>

                    <?php foreach( $slides as $slide ): ?>
                        <div class="item <?php if($slide_no == 1){ echo 'active'; }; ?>">
                            <img src="<?php echo $slide['full_url']; ?>" alt="">
                        </div>
                        <?php $slide_no++ ?>
                    <?php endforeach; ?>

                </div>

                <!-- Controls -->
                <a class="left carousel-control" href="#blog-gallery-slider" data-slide="prev">
                <span class="glyphicon glyphicon-chevron-left"></span>
                </a>
                <a class="right carousel-control" href="#blog-gallery-slider" data-slide="next">
                <span class="glyphicon glyphicon-chevron-right"></span>
                </a>
            </div>
        <?php endif; ?>

        <h2 class="entry-title">
            <a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
            <?php if ( is_sticky() && is_home() && ! is_paged() ) { ?>
            <sup class="featured-post"><?php _e( 'Sticky', 'themeum' ) ?></sup>
            <?php } ?>
        </h2>

        <div class="entry-meta">
            <ul>                                                
                <li class="post-format">
                    <i class="fa fa-picture-o"></i> <a class="entry-format" href="<?php echo esc_url( get_post_format_link( 'gallery' ) ); ?>"><?php echo get_post_format_string( 'gallery' ); ?></a>
                </li>
                <li class="date"><i class="fa fa-clock-o"></i> <time class="entry-date" datetime="<?php the_time( 'c' ); ?>"><?php the_time('j M Y'); ?></time></li>
                <li class="author"><i class="fa fa-pencil"></i> <?php the_author_posts_link() ?></li>
                <li class="category"><i class="fa fa-paperclip"></i> <?php echo get_the_category_list(', '); ?></li> 
                <?php if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) : ?>
                <li class="comments-link">
                    <i class="fa fa-comments-o"></i> <?php comments_popup_link( '<span class="leave-reply">' . __( '0 comment', 'themeum' ) . '</span>', __( 'One comment', 'themeum' ), __( '% comments', 'themeum' ) ); ?>
                </li>
                <?php endif; //.comment-link ?>                     
            </ul>
        </div> <!--/.entry-meta -->

    </header> <!--/.entry-header -->

    <div class="entry-content">
        <?php the_content(); ?>
        <?php wp_link_pages(); ?>
    </div> <!--/.entry-content -->
    
    <?php the_tags( '<footer class="entry-meta"><span class="tag-links"><i class="fa fa-tags"></i>', ', ', '</span></footer>' ); ?>
</article> <!--/#post -->