<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php if ( has_post_thumbnail() && ! post_password_required() ) { ?>
    <header class="entry-header">

        <div class="entry-thumbnail">
            <?php the_post_thumbnail('blog-thumb', array('class' => 'img-responsive')); ?>
        </div>
        
    </header> <!--/.entry-header -->
    <?php } //.entry-thumbnail ?>

    <div class="clearfix post-content media">
        <div class="pull-left">
            <h4 class="post-format">
                <i class="fa fa-paperclip"></i>
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

</article> <!--/#post-->