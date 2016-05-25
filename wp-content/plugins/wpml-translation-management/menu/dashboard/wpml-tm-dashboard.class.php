<?php

class WPML_TM_Dashboard extends WPML_WPDB_User {

	/**
	 * @var array
	 */
	private $active_languages;
	/**
	 * @var string[]
	 */
	private $translatable_types;

	/**
	 * WPML_TM_Dashboard constructor.
	 *
	 * @param wpdb     $wpdb
	 * @param array[]  $active_languages
	 * @param string[] $translatable_types
	 */
	public function __construct( &$wpdb, $active_languages, $translatable_types ) {
		parent::__construct( $wpdb );
		$this->active_languages   = array_keys( $active_languages );
		$this->translatable_types = $translatable_types;
		$this->wpdb               = $wpdb;
	}

    /**
     * get documents
     *
     * @param array $args
     *
     * @return mixed
     */
    public function get_documents( $args ) {
        $parent_id   = false;
        $parent_type = false;
        $to_lang     = false;
        $from_lang   = false;
        $tstatus     = false;
        $title       = false;
        $sort_by     = false;
        $sort_order  = false;
        $status      = false;
        $type        = array();
        $limit_no    = 0;

        extract( $args );

        global $wp_query;
        $wpdb = $this->wpdb;

        if ( !$type ) {
            $type = $this->translatable_types;
        } elseif ( !is_array( $type ) ) {
            $found = false;
            foreach ( $this->translatable_types as $prefixed_type ) {
                if ( strpos( $prefixed_type, '_' . $type ) > 0 ) {
                    $type  = array( $prefixed_type );
                    $found = true;
                    break;
                }
            }
            if ( !$found ) {
                $type = array( $type );
            }
        }

        $title_snippet = $this->build_title_date_snippet( $title );

        $sql = "SELECT  SQL_CALC_FOUND_ROWS
						DISTINCT trid,
						i.element_id AS ID,
						i.element_type AS translation_element_type,
						tr.title,
						(i.source_language_code IS NOT NULL) AS is_translation,
						i.language_code
				FROM {$wpdb->prefix}icl_translations i
				" . $title_snippet . "
				WHERE i.element_id IS NOT NULL
				  AND i.element_type IN (" . wpml_prepare_in( $type, '%s' ) . ")
				";

        $sql = $this->add_from_lang_where( $sql, $from_lang );
        if ( $to_lang ) {
            $sql = $this->add_to_lang_where( $sql, $to_lang );
        }

        $sql = $this->add_status_where( $sql, $status );
        $sql = $this->add_tstatus_where( $sql, $tstatus );
        if ( count( $type ) === 1 ) {
            $sql = $this->add_element_type_where( $sql, array_pop( $type ) );
        }
        if ( $parent_id && $parent_type ) {
            if ( $parent_type === 'page' ) {
                $sql = $this->add_parent_where( $sql, $parent_id );
            } elseif ( $parent_type === 'category' ) {
                $sql = $this->add_category_where( $sql, $parent_id );
            }
        }
        $sql = $this->add_order_snippet( $sql, $sort_by, $sort_order );
        $sql = $this->add_pagination_snippet( $sql, $limit_no );

        $results = $wpdb->get_results( $sql );
        $count   = $wpdb->get_var( "SELECT FOUND_ROWS()" );

        $wp_query->found_posts                    = $count;
        $wp_query->query_vars[ 'posts_per_page' ] = $limit_no;
        $wp_query->max_num_pages                  = ceil( $wp_query->found_posts / $limit_no );

        return $results;
    }

    private function build_title_date_snippet( $title ) {
        $wpdb = $this->wpdb;

        $title_locations = array(
            $wpdb->posts => array(
                'id_column' => 'ID',
                'title_column' => 'post_title',
                'element_type_prefix' => 'post',
                'date_column' => 'post_date'
            )
        );
        $title_locations = apply_filters( 'wpml_tm_dashboard_title_locations', $title_locations );
        $snippets        = array();
        foreach ( $title_locations as $table => $title_info ) {
            $title_info[ 'date_column' ] = $title_info[ 'date_column' ] ? $title_info[ 'date_column' ] : "'none'";
            $snippets[ ]                 = "  SELECT " . $title_info[ 'title_column' ] . " AS title, "
                                           . $title_info[ 'date_column' ] . " AS date, ni.translation_id AS trans_id
                            FROM " . $table . " p JOIN {$wpdb->prefix}icl_translations ni"
                                           . " ON ni.element_type LIKE '" . $title_info[ 'element_type_prefix' ]
                                           . "%' WHERE p." . $title_info[ 'id_column' ] . " = ni.element_id "
                                           . $this->add_search_where(
                    $title_info[ 'title_column' ],
                    $title
                );
        }

        return " JOIN ( " . join( ' UNION ALL', $snippets ) . " ) tr ON tr.trans_id = i.translation_id ";
    }

    private function add_element_type_where( $sql, $type ) {
        if ( $type ) {
            $sql .= $this->wpdb->prepare( " AND i.element_type LIKE %s ", '%' . $type );
        }

        return $sql;
    }

    private function add_status_where( $sql, $status ) {

        $status_snippet = $status ? $this->wpdb->prepare( " = %s ", $status ) : " NOT IN ('auto-draft', 'trash')";
        $sql .=
            " AND ( i.element_type NOT LIKE 'post%' OR (SELECT COUNT(ID)
                                                        FROM {$this->wpdb->posts}
                                                        WHERE ID = i.element_id
                                                            AND post_status {$status_snippet} ) = 1 ) ";

        return $sql;
    }

    private function add_from_lang_where( $sql, $lang ) {
        $sql .= $this->wpdb->prepare( " AND i.language_code = %s ", $lang );

        return $sql;
    }

    private function add_to_lang_where( $sql, $lang ) {
        $wpdb = $this->wpdb;
        if ( $lang ) {
            $sql .= $wpdb->prepare(
                " AND (   SELECT COUNT(translation_id)
												FROM {$wpdb->prefix}icl_translations
												WHERE trid = i.trid AND language_code = %s ) > 0 ",
                $lang
            );
        }

        return $sql;
    }

    private function add_parent_where( $sql, $parent_id ) {
        $wpdb = $this->wpdb;
        $sql .= $this->wpdb->prepare(
            " AND i.element_type LIKE 'post%%'
		                                AND ( SELECT post_parent
                                              FROM {$wpdb->posts}
                                              WHERE ID = i.element_id
                                          ) = %d ",
            $parent_id
        );

        return $sql;
    }

    private function add_category_where( $sql, $cat_id ) {
        $wpdb = $this->wpdb;
        $sql .= $wpdb->prepare(
            " AND i.element_type LIKE 'post%%' AND i.element_id IN
									(   SELECT object_id
										FROM {$wpdb->term_relationships} r JOIN {$wpdb->term_taxonomy} tt
											ON tt.term_taxonomy_id = r.term_taxonomy_id
										WHERE tt.taxonomy = 'category' AND tt.term_id = %d ) ",
            $cat_id
        );

        return $sql;
    }

    private function add_search_where( $column_name, $search ) {
        $wpdb = $this->wpdb;
        return $search ? " AND " . $column_name . " LIKE '%" . $wpdb->esc_like( $search ) . "%' " : "";
    }

    private function add_tstatus_where( $sql, $tstatus ) {
        $wpdb = $this->wpdb;

        if ( $tstatus >= 0 ) {
            if ( $tstatus == ICL_TM_NEEDS_UPDATE ) {
                $condition = " tls.needs_update = 1 ";
            } else {
                if ( in_array( $tstatus, array( ICL_TM_IN_PROGRESS, ICL_TM_WAITING_FOR_TRANSLATOR ) ) ) {
                    $status       = array(
                        ICL_TM_IN_PROGRESS,
                        ICL_TM_WAITING_FOR_TRANSLATOR
                    );
                    $null_snippet = "";
                } elseif ( $tstatus == ICL_TM_COMPLETE ) {
                    $status       = array(
                        ICL_TM_COMPLETE,
                        ICL_TM_DUPLICATE
                    );
                    $null_snippet = "";
                } else {
                    $status       = array( $tstatus );
                    $null_snippet = $wpdb->prepare(
                        "OR tls.status IS NULL AND (SELECT COUNT( trid ) FROM {$wpdb->prefix}icl_translations WHERE trid = i.trid ) < %d ",
                        count( $this->active_languages )
                    );
                }
                $status    = wpml_prepare_in( $status, '%d' );
                $condition = "tls.status IN ({$status}) {$null_snippet}";
            }

            $sql .= " AND  i.trid IN
									(   SELECT trid
										FROM {$wpdb->prefix}icl_translations iclt
											LEFT OUTER JOIN {$wpdb->prefix}icl_translation_status tls
												ON iclt.translation_id = tls.translation_id
										WHERE element_type NOT LIKE 'tax_%%'
											AND ( {$condition} ) ) ";

        }

        return $sql;
    }

    private function add_order_snippet( $sql, $sort_by, $sort_order ) {
        if ( $sort_by ) {
            $order = "tr.{$sort_by} ";
        } else {
            $order = " tr.date ";
        }
        if ( $sort_order ) {
            $order .= $sort_order;
        } else {
            $order .= ' DESC ';
        }

        $sql .= " ORDER BY {$order} ";

        return $sql;
    }

    private function add_pagination_snippet( $sql, $limit_no ) {
        $paged  = filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT );
        $paged  = $paged ? $paged : 1;
        $offset = ( $paged - 1 ) * $limit_no;
        $limit  = " " . $offset . ',' . $limit_no;
        $sql .= " LIMIT {$limit}";

        return $sql;
    }
}
