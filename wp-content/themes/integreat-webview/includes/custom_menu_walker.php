<?php

    class Integreat_Walker_Menu extends Walker_Page {

        /**
         * @see Walker::start_el()
         * @since 2.1.0
         *
         * @param string $output       Passed by reference. Used to append additional content.
         * @param object $page         Page data object.
         * @param int    $depth        Depth of page. Used for padding.
         * @param int    $current_page Page ID.
         * @param array  $args
         */
        public function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
            if ( $depth ) {
                $indent = str_repeat( "\t", $depth );
            } else {
                $indent = '';
            }

            $css_class = array( 'page_item', 'page-item-' . $page->ID );

            if ( isset( $args['pages_with_children'][ $page->ID ] ) ) {
                $css_class[] = 'page_item_has_children';
            }

            if ( ! empty( $current_page ) ) {
                $_current_page = get_post( $current_page );
                if ( $_current_page && in_array( $page->ID, $_current_page->ancestors ) ) {
                    $css_class[] = 'current_page_ancestor';
                }
                if ( $page->ID == $current_page ) {
                    $css_class[] = 'current_page_item';
                } elseif ( $_current_page && $page->ID == $_current_page->post_parent ) {
                    $css_class[] = 'current_page_parent';
                }
            } elseif ( $page->ID == get_option('page_for_posts') ) {
                $css_class[] = 'current_page_parent';
            }

            /**
             * Filter the list of CSS classes to include with each page item in the list.
             *
             * @since 2.8.0
             *
             * @see wp_list_pages()
             *
             * @param array   $css_class    An array of CSS classes to be applied
             *                             to each list item.
             * @param WP_Post $page         Page data object.
             * @param int     $depth        Depth of page, used for padding.
             * @param array   $args         An array of arguments.
             * @param int     $current_page ID of the current page.
             */
            $css_classes = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

            if ( '' === $page->post_title ) {
                /* translators: %d: ID of a post */
                $page->post_title = sprintf( __( '#%d (no title)' ), $page->ID );
            }

            $args['link_before'] = empty( $args['link_before'] ) ? '' : $args['link_before'];
            $args['link_after'] = empty( $args['link_after'] ) ? '' : $args['link_after'];

            if( $thumbID = get_post_thumbnail_id($page->ID) ) {
                $thumb = wp_get_attachment_image( $thumbID, 'thumb' );
            } else {
                $thumb = '<img src="'. get_stylesheet_directory_uri() .'/images/standardMenuIcon.png" />';
            }

            /** This filter is documented in wp-includes/post-template.php */
            $output .= $indent . sprintf(
                    '<li class="%s"><a href="%s"><span class="menu-icon">%s</span><span class="linkText"><span>%s%s%s</span></span><span class="childrenToggle"><i class="fa fa-plus"></i><span class="borderBottom"></span></span></a>',
                    $css_classes,
                    get_permalink( $page->ID ),
                    $thumb,
                    $args['link_before'],
                    apply_filters( 'the_title', $page->post_title, $page->ID ),
                    $args['link_after']
                );

            if ( ! empty( $args['show_date'] ) ) {
                if ( 'modified' == $args['show_date'] ) {
                    $time = $page->post_modified;
                } else {
                    $time = $page->post_date;
                }

                $date_format = empty( $args['date_format'] ) ? '' : $args['date_format'];
                $output .= " " . mysql2date( $date_format, $time );
            }
        }

    }

?>