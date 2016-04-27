<?php
require ICL_PLUGIN_PATH . '/menu/term-taxonomy-menus/wpml-translation-tree.class.php';

	define( 'WPML_TT_TAXONOMIES_NOT_TRANSLATED', 1 );
	define( 'WPML_TT_TAXONOMIES_ALL', 0 );
	// This sets the number of rows in the table to be displayed by this class, not the actual number of terms.
	define( 'WPML_TT_TERMS_PER_PAGE', 10 );

	class WPML_Taxonomy_Translation {

		private $taxonomy      = '';
		private $tax_selector  = true;
		private $show_tax_sync = false;
		private $taxonomy_obj  = false;

		public function __construct( $taxonomy = '', $args = array() ) {

			/**
			 * Sets whether a taxonomy selector or only a specific taxonomy is to be shown.
			 * @var bool $tax_selector
			 */
			$this->tax_selector  = isset( $args[ 'taxonomy_selector' ] ) ? $args[ 'taxonomy_selector' ] : true;
			$this->taxonomy      = $taxonomy ? $taxonomy : false;
			$this->show_tax_sync = isset( $args[ 'taxonomy_sync' ] ) ? $args[ 'taxonomy_sync' ] : false;

			if ( $taxonomy ) {
				$this->taxonomy_obj = get_taxonomy( $taxonomy );
			}
		}

		public function render() {

			WPML_Taxonomy_Translation_Table_Display::enqueue_taxonomy_table_js();
			$output = '<div class="wrap">';

			if ( $this->taxonomy ) {
				$output .= '<input type="hidden" id="tax-preselected" value="' . $this->taxonomy . '">';
			}
			if ( ! $this->tax_selector ) {
				$output .= '<input type="hidden" id="tax-selector-hidden" value="1"/>';
			}

			$output .= '<div id="icon-wpml" class="icon32" style="clear:both"><br/></div>';

			if ( $this->tax_selector ) {
				$output .= '<h1>' . __( 'Taxonomy Translation', 'sitepress' ) . '</h1>';

				$output .= '<br/>';
			}
			$output .= '<div id="wpml_tt_taxonomy_translation_wrap">';
            $output .= '<div class="loading-content"><span class="spinner" style="visibility: visible"></span></div>';
			$output .= '</div>';

			do_action( 'icl_menu_footer' );
			$output .= apply_filters( 'wpml_taxonomy_translation_bottom', $html = '', $this->taxonomy, $this->taxonomy_obj );
			echo $output;

			echo '</div>';
		}

		/**
		 * @param $taxonomy string The taxonomy currently displayed
		 *
		 * @return array holding the terms to be displayed and the overall count of terms in the given taxonomy
		 */
		public static function get_terms_for_taxonomy_translation_screen( $taxonomy ) {
			global $wpdb;

			/*
			 * The returned array from this function is indexed as follows.
			 * It holds an array of all terms to be displayed under [terms]
			 * and the count of all terms matching the filter under [count].
			 *
			 * The array under [terms] itself is index as such:
			 * [trid][lang]
			 *
			 * It holds in itself the terms objects of the to be displayed terms.
			 * These are ordered by their names alphabetically.
			 * Also their objects are amended by the index $term->translation_of holding the term_taxonomy_id of their original element
			 * and their level under $term->level in case of hierarchical terms.
			 *
			 * Also the index [trid][source_lang] holds the source language of the term group.
			 */

			// Only look for terms in active languages when checking for untranslated ones.

			$attributes_to_select                                 = array();
			$icl_translations_table_name                          = $wpdb->prefix . 'icl_translations';
			$attributes_to_select[ $wpdb->terms ]                 = array(
				'alias' => 't',
				'vars'  => array( 'name', 'slug', 'term_id' )
			);
			$attributes_to_select[ $wpdb->term_taxonomy ]         = array(
				'alias' => 'tt',
				'vars'  => array(
					'term_taxonomy_id',
					'parent',
					'description'
				)
			);
			$attributes_to_select[ $icl_translations_table_name ] = array(
				'alias' => 'i',
				'vars'  => array(
					'language_code',
					'trid',
					'source_language_code'
				)
			);

			$join_statements = array();

			$as = self::alias_statements( $attributes_to_select );

			$join_statements [ ] = "{$as['t']} JOIN {$as['tt']} ON tt.term_id = t.term_id";
			$join_statements [ ] = "{$as['i']} ON i.element_id = tt.term_taxonomy_id";


			$from_clause = join( ' JOIN ', $join_statements );

			$select_clause = self::build_select_vars( $attributes_to_select );

			$where_clause = self::build_where_clause( $attributes_to_select, $taxonomy );

			$full_statement = "SELECT {$select_clause} FROM {$from_clause} WHERE {$where_clause}";

			$all_terms = $wpdb->get_results( $full_statement );
			if ( $all_terms ) {
				$all_terms_indexed = self::index_terms_array( $all_terms );
				$all_terms_grouped = self::order_terms_list( $all_terms_indexed, $taxonomy );
				return $all_terms_grouped;

			}
		}

		/**
		 * @param $terms array
		 *               Turn a numerical array of terms objects into an associative once,
		 *               holding the same terms, but indexed by their term_id.
		 *
		 * @return array
		 */
		private static function index_terms_array( $terms ) {
			$terms_indexed = array();

			foreach ( $terms as $term ) {
				$terms_indexed[ $term->term_id ] = $term;
			}

			return $terms_indexed;
		}

		/**
		 * @param $trid_group array
		 * @param $terms      array
		 *                    Transforms the term arrays generated by the Translation Tree class and turns them into
		 *                    standard WordPress terms objects.
		 *
		 * @return mixed
		 */
		private static function set_language_information( $trid_group, $terms ) {

			foreach ( $trid_group[ 'elements' ] as $lang => $term ) {

				$term_object         = $terms[ $term[ 'term_id' ] ];
				$term_object->level  = $term[ 'level' ];
				$trid_group[ $lang ] = $term_object;
			}

			unset( $trid_group[ 'elements' ] );

			return $trid_group;
		}

		/**
		 * @param $terms    array
		 * @param $taxonomy string
		 *                  Orders a list of terms alphabetically and hierarchy-wise
		 *
		 * @return array
		 */
		private static function order_terms_list( $terms, $taxonomy ) {
			global $wpdb, $sitepress;

			$terms_tree    = new WPML_Translation_Tree( $wpdb, $sitepress, $taxonomy, false, $terms );
			$ordered_terms = $terms_tree->get_alphabetically_ordered_list();
			foreach ( $ordered_terms as $key => $trid_group ) {
				$ordered_terms[ $key ] = self::set_language_information( $trid_group, $terms );
			}

			return $ordered_terms;
		}

		/**
		 * @param $selects array
		 *                 Generates a list of to be selected variables in an sql query.
		 *
		 * @return string
		 */
		private static function build_select_vars( $selects ) {
			$output = '';

			if ( is_array( $selects ) ) {
				$coarse_selects = array();

				foreach ( $selects as $select ) {

					$vars  = $select[ 'vars' ];
					$table = $select[ 'alias' ];

					foreach ( $vars as $key => $var ) {
						$vars[ $key ] = $table . '.' . $var;
					}
					$coarse_selects[ ] = join( ', ', $vars );
				}

				$output = join( ', ', $coarse_selects );
			}

			return $output;
		}

		/**
		 * @param $selects array
		 *                 Returns an array of alias statements to be used in SQL queries with joins.
		 *
		 * @return array
		 */
		private static function alias_statements( $selects ) {
			$output = array();
			foreach ( $selects as $key => $select ) {
				$output[ $select[ 'alias' ] ] = $key . ' AS ' . $select[ 'alias' ];
			}

			return $output;
		}

		private static function build_where_clause( $selects, $taxonomy ) {
			global $wpdb;

			$where_clauses[ ] = $selects[ $wpdb->term_taxonomy ][ 'alias' ]
                                . $wpdb->prepare(".taxonomy = %s ", $taxonomy);
			$where_clauses[ ] = $selects[ $wpdb->prefix . 'icl_translations' ][ 'alias' ]
                                . $wpdb->prepare(".element_type = %s ", 'tax_' . $taxonomy);

			$where_clause = join( ' AND  ', $where_clauses );

			return $where_clause;
		}
	}
