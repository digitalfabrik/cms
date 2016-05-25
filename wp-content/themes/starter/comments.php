<?php
/* This comments template */

if ( post_password_required() )
    return;
?>
<div id="comments" class="comments-area comments">
    <?php if ( have_comments() ) : ?>
        <h3 class="comments-title">
            <?php comments_number( __('No Comment', 'themeum' ), __('One Comments', 'themeum' ), __('% Comments', 'themeum' ) ); ?>
        </h3>

        <ul class="comment-list">

            <?php
                wp_list_comments( array(
                    'style'       => 'ul',
                    'short_ping'  => true,
                    'callback' => 'themeum_comment',
                    'avatar_size' => 64
                ) );
            ?>
        </ul><!-- .comment-list -->

        <?php
            // Are there comments to navigate through?
            if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
        ?>
        <nav class="navigation comment-navigation" role="navigation">
            <h1 class="screen-reader-text section-heading"><?php _e( 'Comment navigation', 'themeum' ); ?></h1>
            <div class="nav-previous"><?php previous_comments_link( __( '&larr; Older Comments', 'themeum' ) ); ?></div>
            <div class="nav-next"><?php next_comments_link( __( 'Newer Comments &rarr;', 'themeum' ) ); ?></div>
        </nav><!-- .comment-navigation -->
        <?php endif; // Check for comment navigation ?>

        <?php if ( ! comments_open() && get_comments_number() ) : ?>
        <p class="no-comments"><?php _e( 'Comments are closed.' , 'themeum' ); ?></p>
        <?php endif; ?>

    <?php endif; // have_comments() ?>

    <?php
        $commenter = wp_get_current_commenter();
        $req = get_option( 'require_name_email' );
        $aria_req = ( $req ? " aria-required='true'" : '' );
        $fields =  array(
            'author' => '<div class="col-md-4"><input id="author" name="author" type="text" placeholder="name" value="" size="30"' . $aria_req . '/></div>',
            'email'  => '<div class="col-md-4"><input id="email" name="email" type="text" placeholder="email" value="" size="30"' . $aria_req . '/></div>',
            'url'  => '<div class="col-md-4"><input id="url" name="url" type="text" placeholder="website url" value="" size="30"/></div>',
        );
         
        $comments_args = array(
            'fields' =>  $fields,
            'comment_notes_before'      => '',
            'comment_notes_after'       => '',
            'comment_field'             => '<div class="clearfix"></div><div class="col-md-12"><textarea id="comment" placeholder="comment" name="comment" aria-required="true"></textarea></div>',
            'label_submit'              => 'Send Comment'
        );
        ob_start();
        comment_form($comments_args);
        echo str_replace('class="comment-form"','class="comment-form row"',ob_get_clean());
        echo str_replace('class="form-submit"','class="form-submit col-md-12"',ob_get_clean());
    ?>
</div>