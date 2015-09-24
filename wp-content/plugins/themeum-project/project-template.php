<?php get_header(); ?>

    <section class="container">
        <div class="row">
            <div id="content" class="site-content col-md-offset-2 col-md-8" role="main">

                <?php if ( have_posts() ) : ?>

                    <?php while ( have_posts() ) : the_post(); ?>

                        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                            <header class="entry-header">

                                <?php if ( has_post_thumbnail() && ! post_password_required() ) { ?>
                                <div class="entry-thumbnail">
                                    <?php the_post_thumbnail('full', array('class' => 'img-responsive')); ?>
                                </div>
                                <?php } //.entry-thumbnail ?>


                                <h2 class="entry-title">
                                    <a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
                                    <?php if ( is_sticky() && is_home() && ! is_paged() ) { ?>
                                    <sup class="featured-post"><?php _e( 'Sticky', 'themeum' ) ?></sup>
                                    <?php } ?>
                                </h2>

                                <?php
                                    $terms = get_the_terms( $post->ID, 'project_tag' );

                                    if ( $terms && ! is_wp_error( $terms ) )
                                    { 
                                        $term_name = array();
                                        $term_names = '';

                                        foreach ( $terms as $term ) {
                                            $term_name[] = $term->name;
                                        }

                                        $term_names = join( ", ", $term_name );
                                    }
                                ?>

                                <div class="entry-meta">
                                    <ul>
                                        <li class="date"><i class="fa fa-clock-o"></i> <time class="entry-date" datetime="<?php the_time( 'c' ); ?>"><?php the_time('j M Y'); ?></time></li>
                                        <li class="author"><i class="fa fa-pencil"></i> <?php the_author_posts_link() ?></li>
                                        <li class="category"><i class="fa fa-paperclip"></i> <?php echo $term_names; ?></li> 
                                        <?php if ( ! post_password_required() && ( comments_open() || get_comments_number() ) ) : ?>
                                        <li class="comments-link">
                                            <i class="fa fa-comments-o"></i> <?php comments_popup_link( '<span class="leave-reply">' . __( 'Leave a comment', 'themeum' ) . '</span>', __( 'One comment so far', 'themeum' ), __( 'View all % comments', 'themeum' ) ); ?>
                                        </li>
                                        <?php endif; //.comment-link ?>
                                    </ul>
                                </div> <!--/.entry-meta -->

                            </header> <!--/.entry-header -->

                            <div class="entry-content">
                                <?php the_content(); ?>
                                
                                <?php wp_link_pages(); ?>
                            </div> <!-- //.entry-content -->

                            <?php the_tags( '<footer class="entry-meta"><span class="tag-links"><i class="fa fa-tags"></i>', ', ', '</span></footer>' ); ?>
                        </article> <!--/#post-->

                        <div class="clearfix post-navigation">
                            <?php previous_post_link('<span class="previous-post">%link</span>'); ?>
                             <?php next_post_link('<span class="next-post">%link</span>'); ?>
                        </div> <!-- .post-navigation -->

                        <?php
                            if ( comments_open() || get_comments_number() ) {
                                    comments_template();
                            }
                        ?>
                    <?php endwhile; ?>
                    
                <?php else: ?>
                    <?php get_template_part( 'post-format/single', 'none' ); ?>
                <?php endif; ?>

            </div> <!-- #content -->
        </div> <!-- .row -->
    </section> <!-- .container -->

<?php get_footer();