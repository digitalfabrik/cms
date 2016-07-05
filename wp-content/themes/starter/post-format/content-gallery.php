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
                            <?php $images = wp_get_attachment_image_src( $slide['ID'], 'blog-thumb' ); ?>
                            <img src="<?php echo $images[0]; ?>" alt="">
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

    </header> <!--/.entry-header -->

    <div class="clearfix post-content media">
        <div class="pull-left">
            <h4 class="post-format">
                <i class="fa fa-picture-o"></i>
            </h4>
            <h6 class="publish-date">
                <time class="entry-date" datetime="<?php the_time( 'c' ); ?>"><?php the_time('j M Y'); ?></time>
            </h6>
        </div>
        <div class="media-body">
            <h2 class="entry-title">
                <a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
                <?php if ( is_sticky() && is_home() && ! is_paged() ) { ?>
                <sup class="featured-post"><?php _e( 'Sticky', 'themeum' ) ?></sup>
                <?php } ?>
            </h2> <!-- //.entry-title -->

            <div class="clearfix entry-meta">
                <ul>
                    <li class="author-category"><i class="fa fa-pencil"></i> BY <?php the_author_posts_link() ?> IN <?php echo get_the_category_list(', '); ?></li>
                    <?php if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) : ?>
                        <li class="comments-link">
                            <i class="fa fa-comments-o"></i> <?php comments_popup_link( '<span class="leave-reply">' . __( 'No comment', 'themeum' ) . '</span>', __( 'One comment', 'themeum' ), __( '% comments', 'themeum' ) ); ?>
                        </li>
                    <?php endif; //.comment-link ?>
                </ul>
            </div> <!--/.entry-meta -->
            <div class="entry-summary">
                <?php the_excerpt(); ?>
            </div> <!-- //.entry-summary -->
        </div>
    </div>

    <?php the_tags( '<footer class="entry-meta"><span class="tag-links">', '', '</span></footer>' ); ?>

</article> <!--/#post -->