<?php
require_once WPML_TM_PATH . '/menu/wpml-translation-editor-ui.class.php';

/**
 * @var TranslationManagement $iclTranslationManagement
 * @var WPML_Translation_Job_Factory $wpml_translation_job_factory
 */
global $wp_query, $sitepress, $iclTranslationManagement, $current_user, $wpml_translation_job_factory;
if ( ! isset( $job_checked )
     && ( ( isset( $_GET['job_id'] ) && $_GET['job_id'] > 0 )
          || ( isset( $_GET['trid'] ) && $_GET['trid'] > 0 ) )
) {
	$job_id     = WPML_Translation_Editor::get_job_id_from_request();
	$job_object = $wpml_translation_job_factory->get_translation_job( $job_id, false, 0, true );
	if ( is_a( $job_object, 'WPML_Post_Translation_Job' ) ) {
		/** @var WPML_Post_Translation_Job $job_object */
		$job_object->load_terms_from_post_into_job();
	}
	if ( $job_object && $job_object->user_can_translate( $current_user ) ) {
		$translation_editor_ui = new WPML_Translation_Editor_UI( $sitepress,
		                                                         $iclTranslationManagement,
		                                                         $job_object );
		$translation_editor_ui->render();

		return;
	}
}
if ( ! empty( $_GET[ 'resigned' ] ) ) {
    $iclTranslationManagement->add_message( array(
                                                'type' => 'updated',
                                                'text' => __( "You've resigned from this job.",
                                                              'wpml-translation-management' )
                                            ) );
}
if ( isset( $_SESSION[ 'translation_ujobs_filter' ] ) ) {
    $icl_translation_filter = $_SESSION[ 'translation_ujobs_filter' ];
}
$current_translator = $iclTranslationManagement->get_current_translator();
$can_translate      = $current_translator && $current_translator->ID > 0 && $current_translator->language_pairs;
$post_link_factory  = new WPML_TM_Post_Link_Factory( $sitepress );
if( $can_translate ) {
	$icl_translation_filter['translator_id']      = $current_translator->ID;
	$icl_translation_filter['include_unassigned'] = true;

	$element_type_prefix = isset( $_GET['element_type'] ) ? $_GET['element_type'] : 'post';
	if ( isset( $_GET['updated'] ) && $_GET['updated'] ) {
		$post                 = get_post( $_GET['updated'] );
		$tm_post_link_updated = $post_link_factory->view_link( $_GET['updated'] );
		if ( $iclTranslationManagement->is_external_type( $element_type_prefix ) ) {
			$tm_post_link_updated = apply_filters( 'wpml_external_item_link', $tm_post_link_updated, $_GET['updated'], false );
		}
		$user_message = __( 'Translation updated: ', 'wpml-translation-management' ) . $tm_post_link_updated;
		$iclTranslationManagement->add_message( array( 'type' => 'updated', 'text' => $user_message ) );
	} elseif ( isset( $_GET['added'] ) && $_GET['added'] ) {
		$post               = get_post( $_GET['added'] );
		$tm_post_link_added = $post_link_factory->view_link( $_GET['added'] );
		if ( $iclTranslationManagement->is_external_type( $element_type_prefix ) ) {
			$tm_post_link_added = apply_filters( 'wpml_external_item_link', $tm_post_link_added, $_GET['added'], false );
		}
		$user_message = __( 'Translation added: ', 'wpml-translation-management' ) . $tm_post_link_added;
		$iclTranslationManagement->add_message( array( 'type' => 'updated', 'text' => $user_message ) );
	} elseif ( isset( $_GET['job-cancelled'] ) ) {
		$user_message = __( 'Translation has been removed by admin', 'wpml-translation-management' );
		$iclTranslationManagement->add_message( array( 'type' => 'error', 'text' => $user_message ) );
	}

	$translation_jobs = array();

	if ( ! empty( $current_translator->language_pairs ) ) {
		$_langs_to = array();
		if ( 1 < count( $current_translator->language_pairs ) ) {
			foreach ( $current_translator->language_pairs as $lang => $to ) {
				$langs_from[] = $sitepress->get_language_details( $lang );
				$_langs_to    = array_merge( (array) $_langs_to, array_keys( $to ) );
			}
			$_langs_to = array_unique( $_langs_to );
		} else {
			$_langs_to                      = array_keys( current( $current_translator->language_pairs ) );
			$lang_from                      = $sitepress->get_language_details( key( $current_translator->language_pairs ) );
			$icl_translation_filter['from'] = $lang_from['code'];
		}

		if ( 1 < count( $_langs_to ) ) {
			foreach ( $_langs_to as $lang ) {
				$langs_to[] = $sitepress->get_language_details( $lang );
			}
		} else {
			$lang_to                      = $sitepress->get_language_details( current( $_langs_to ) );
			$icl_translation_filter['to'] = $lang_to['code'];
		}

		$job_types        = $iclTranslationManagement->get_translation_job_types( array(
																																								'translator_id'      => $current_translator->ID,
																																								'include_unassigned' => true
																																							) );
		$translation_jobs = $iclTranslationManagement->get_translation_jobs( (array) $icl_translation_filter );

		$post_types      = $sitepress->get_translatable_documents( true );
		$post_types      = apply_filters( 'wpml_get_translatable_types', $post_types );
		$post_type_names = array();
	}
}
?>
<div class="wrap icl_tm_wrap">
    <div id="icon-wpml" class="icon32"><br /></div>
    <h2><?php echo __('Translations queue', 'wpml-translation-management') ?></h2>    
    
    <?php if(empty($current_translator->language_pairs)): ?>
    <div class="error below-h2"><p><?php _e("No translation languages configured for this user.", 'wpml-translation-management'); ?></p></div>
    <?php endif; ?>
    <?php do_action('icl_tm_messages'); ?>
    
    
    <?php if(!empty($current_translator->language_pairs)): ?>
    <form method="post" name="translation-jobs-filter" action="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/translations-queue.php">
    <input type="hidden" name="icl_tm_action" value="ujobs_filter" />
    <table class="form-table widefat fixed">
        <thead>
        <tr>
            <th scope="col"><strong><?php _e('Filter by','wpml-translation-management')?></strong></th>
        </tr>
        </thead> 
        <tbody>
            <tr valign="top">
                <td>
                    <label>
                        <strong><?php _e('Status', 'wpml-translation-management')?></strong>&nbsp;
                        <select name="filter[status]">
                            <option value=""><?php _e('All', 'wpml-translation-management')?></option>
                            <option value="<?php echo ICL_TM_COMPLETE ?>" <?php 
                                if(@intval($icl_translation_filter['status'])==ICL_TM_COMPLETE):?>selected="selected"<?php endif ;?>><?php 
                                    echo TranslationManagement::status2text(ICL_TM_COMPLETE); ?></option>
                            <option value="<?php echo ICL_TM_IN_PROGRESS ?>" <?php 
                                if(@intval($icl_translation_filter['status'])==ICL_TM_IN_PROGRESS):?>selected="selected"<?php endif ;?>><?php 
                                    echo TranslationManagement::status2text(ICL_TM_IN_PROGRESS); ?></option>
                            <option value="<?php echo ICL_TM_WAITING_FOR_TRANSLATOR ?>" <?php 
                                if(@intval($icl_translation_filter['status']) 
                                    && $icl_translation_filter['status']== ICL_TM_WAITING_FOR_TRANSLATOR):?>selected="selected"<?php endif ;?>><?php 
                                    _e('Available to translate', 'wpml-translation-management') ?></option>                                    
                        </select>
                    </label>&nbsp;
                    <label>
                        <strong><?php _e('From', 'wpml-translation-management');?></strong>
                            <?php if(1 < count($current_translator->language_pairs)): ?>
                            <select name="filter[from]">   
                                <option value=""><?php _e('Any language', 'wpml-translation-management')?></option>
                                <?php foreach($langs_from as $lang):?>
                                <option value="<?php echo $lang['code']?>" <?php 
                                if(isset($icl_translation_filter['from']) && $icl_translation_filter['from']==$lang['code']):?>selected="selected"<?php endif ;?>><?php echo $lang['display_name']?></option>
                                <?php endforeach; ?>
                            </select>                            
                            <?php else: ?>
                            <input type="hidden" name="filter[from]" value="<?php echo esc_attr($lang_from['code']) ?>" />   
                            <?php echo $lang_from['display_name']; ?>                            
                            <?php endif; ?>
                    </label>&nbsp;        
                    <label>
                        <strong><?php _e('To', 'wpml-translation-management');?></strong>
                            <?php if(1 < @count($langs_to)): ?>
                            <select name="filter[to]">   
                                <option value=""><?php _e('Any language', 'wpml-translation-management')?></option>
                                <?php foreach($langs_to as $lang):?>
                                <option value="<?php echo $lang['code']?>" <?php 
                                if(!empty($icl_translation_filter['to']) && $icl_translation_filter['to']==$lang['code']):?>selected="selected"<?php endif ;?>><?php echo $lang['display_name']?></option>
                                <?php endforeach; ?>
                            </select>            
                            <?php else: ?>
                            <input type="hidden" name="filter[to]" value="<?php echo esc_attr($lang_to['code']) ?>" />   
                            <?php echo $lang_to['display_name']; ?>
                            <?php endif; ?>
                    </label>                
                    <label>
                        <strong><?php _e('Type', 'wpml-translation-management');?></strong>
                            <select name="filter[type]">   
                                <option value=""><?php _e('Any type', 'wpml-translation-management')?></option>
                                <?php foreach($job_types as $job_type => $job_type_name):?>
								<option value="<?php echo $job_type?>" <?php 
                                if(!empty($icl_translation_filter['type']) && $icl_translation_filter['type'] == $job_type):?>selected="selected"<?php endif ;?>><?php echo $job_type_name?></option>
                                <?php endforeach; ?>
                            </select>            
                    </label>                
                    &nbsp;
                    <input class="button-secondary" type="submit" value="<?php _e('Apply', 'wpml-translation-management')?>" />
                </td>
            </tr>
        </tbody>     
    </table>
    </form>    

    <br />

        <?php
        // See if we have any bulk actions to do.
        $actions = apply_filters( 'WPML_translation_queue_actions', array() );
        ?>
        <?php if ( sizeof( $actions ) > 0 ): ?>
            <form method="post" name="translation-jobs-action" action="admin.php?page=<?php echo WPML_TM_FOLDER ?>/menu/translations-queue.php">
        <?php endif; ?>

        <?php
        // pagination
        $paged = filter_input(INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT);
        $paged = $paged ? $paged : 1;
        $total_count     = count( $translation_jobs );
        $args            = array(
            'base'      => add_query_arg( 'paged', '%#%' ),
            'format'    => '',
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'current'   => $paged,
            'add_args'  => isset( $icl_translation_filter ) ? $icl_translation_filter : array(),
            'per_page'  => isset( $_GET[ 'show_all' ] ) && $_GET[ 'show_all' ] ? $total_count : 20
        );
        $args[ 'total' ] = ceil( $total_count / $args[ 'per_page' ] );
        $page_links      = paginate_links( $args );

        $translation_jobs = array_slice( $translation_jobs,
                                         ( $args[ 'current' ] - 1 ) * $args[ 'per_page' ],
                                         $args[ 'per_page' ] );
        ?>
        <div class="tablenav">
            <div
                class="tablenav-pages">
                <?php if ( $page_links ) { ?>
                    <?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s',
                                                                                            'wpml-translation-management' ) . '</span>%s',
                                                      number_format_i18n( ( $args[ 'current' ] - 1 ) * $args[ 'per_page' ] + 1 ),
                                                      number_format_i18n( min( $args[ 'current' ] * $args[ 'per_page' ],
                                                                               $total_count ) ),
                                                      number_format_i18n( $total_count ),
                                                      $page_links
                    );
                    if ( ! isset( $_GET[ 'show_all' ] ) && $total_count > $args[ 'per_page' ] ) {
                        echo '<a style="width: auto; font-weight:normal;" href="' . admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&show_all=1' ) . '">' . __( 'Show All',
                                                                                                                                                                               'wpml-translation-management' ) . '</a>';
                    }
                    echo $page_links_text; ?>

                <?php } elseif ( $args[ 'per_page' ] > 20 ) {
                    echo '<a style="width: auto; font-weight:normal" href="' . admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php' ) . '">' . __( 'Show 20',
                                                                                                                                                                'wpml-translation-management' ) . '</a>';
                } ?>
            </div>
            <?php do_action( "WPML_xliff_select_actions", $actions, "action" ); ?>
        </div>
        <?php // pagination - end ?>

	    <table class="widefat fixed" id="icl-translation-jobs" cellspacing="0">
		    <thead>
		    <tr>
			    <?php if ( sizeof( $actions ) > 0 ): ?>
				    <th class="manage-column column-cb check-column" scope="col"><input title="<?php _e('Check all', 'wpml-translation-management'); ?>" type="checkbox" /></th>
			    <?php endif; ?>

			    <th scope="col" width="60"><?php _e( 'Job ID', 'wpml-translation-management' ) ?></th>
			    <th scope="col"><?php _e( 'Title', 'wpml-translation-management' ) ?></th>
			    <th scope="col"><?php _e( 'Type', 'wpml-translation-management' ) ?></th>
			    <th scope="col" class="column-language"><?php _e( 'Language', 'wpml-translation-management' ) ?></th>
			    <th scope="col" class="manage-column">&nbsp;</th>
			    <th scope="col" class="manage-column column-date" style="width:14px;">&nbsp;</th>
			    <th scope="col" class="column-status"><?php _e( 'Status', 'wpml-translation-management' ) ?></th>
			    <th scope="col" class="manage-column column-date column-resign">&nbsp;</th>
		    </tr>
		    </thead>
		    <tfoot>
		    <tr>
			    <?php if ( sizeof( $actions ) > 0 ): ?>
				    <th class="manage-column column-cb check-column" scope="col"><input title="<?php _e('Check all', 'wpml-translation-management'); ?>" type="checkbox" /></th>
			    <?php endif; ?>

			    <th scope="col" width="60"><?php _e( 'Job ID', 'wpml-translation-management' ) ?></th>
			    <th scope="col"><?php _e( 'Title', 'wpml-translation-management' ) ?></th>
			    <th scope="col"><?php _e( 'Type', 'wpml-translation-management' ) ?></th>
			    <th scope="col" class="column-language"><?php _e( 'Language', 'wpml-translation-management' ) ?></th>
			    <th scope="col">&nbsp;</th>
			    <th scope="col">&nbsp;</th>
					<th scope="col" class="column-status"><?php _e( 'Status', 'wpml-translation-management' ) ?></th>
			    <th scope="col" class="manage-column column-date column-resign">&nbsp;</th>
		    </tr>
		    </tfoot>
		    <tbody>
		    <?php if ( empty( $translation_jobs ) ): ?>
			    <tr>
				    <td colspan="7" align="center"><?php _e( 'No translation jobs found', 'wpml-translation-management' ) ?></td>
			    </tr>
		    <?php else: foreach ( $translation_jobs as $job ): ?>
			    <tr>
				    <?php
				    if ( sizeof( $actions ) > 0 ){
					    ?>
					    <td>
						    <label><input type="checkbox" name="job[<?php echo $job->job_id ?>]" value="1" />&nbsp;</label>
					    </td>
				    <?php
				    }
				    ?>

                <td width="60"><?php echo $job->job_id; ?></td>
                <td><?php echo esc_html(apply_filters('the_title', $job->post_title )); ?></td>
				<?php
					if ( ! isset( $post_type_names[ $job->original_post_type ] ) ) {
						$type = $job->original_post_type;
						$name = $type;
						switch ( $job->element_type_prefix ) {
							case 'post':
								$type = substr( $type, 5 );
								break;
							
							case 'package':
								$type = substr( $type, 8 );
								break;
						}
			
						if ( isset( $post_types[ $type ]) ) {
							$name = $post_types[ $type ]->labels->singular_name;
						}
						
						$post_type_names [ $job->original_post_type ] = $name;
					}
				?>
                <td><?php echo esc_html( $post_type_names[ $job->original_post_type ] ); ?></td>
                <td><?php echo $job->lang_text ?></td>
                <td>
		            <?php
		            if ( $job->original_doc_id ) {
			            $translation_queue_page = admin_url( 'admin.php?page=' . WPML_TM_FOLDER . '/menu/translations-queue.php&job_id=' . $job->job_id );
			            $icl_job_edit_url       = apply_filters( 'icl_job_edit_url', $translation_queue_page, $job->job_id );

			            ?>
			            <a class="button-secondary" href="<?php echo $icl_job_edit_url; ?>">
				            <?php
				            $needs_edit  = in_array( $job->status, array( ICL_TM_WAITING_FOR_TRANSLATOR, ICL_TM_IN_PROGRESS, ICL_TM_COMPLETE ) );
				            $is_editable = $job->translator_id > 0 && $needs_edit;
				            if ( $is_editable ) {
					            _e( 'Edit', 'wpml-translation-management' );
				            } else {
					            _e( 'Take this and edit', 'wpml-translation-management' );
				            }
				            ?>
			            </a>
			            <?php
			            $tm_post_link        = $post_link_factory->view_link_anchor( $job->original_doc_id,
				            __( 'View original',
					            'wpml-translation-management' ) );
			            $element_type_prefix = $iclTranslationManagement->get_element_type_prefix_from_job( $job );
			            if ( $iclTranslationManagement->is_external_type( $element_type_prefix ) ) {
				            $tm_post_link = apply_filters( 'wpml_external_item_link', '', $job->original_doc_id, false );
			            }

			            $original_element_type = $job->original_post_type;
			            $original_element_type = explode( '_', $original_element_type );
			            if ( count( $original_element_type ) > 1 ) {
				            unset( $original_element_type[ 0 ] );
			            }
			            $original_element_type = join( '_', $original_element_type );

			            $tm_post_link = apply_filters( 'wpml_document_view_item_link', $tm_post_link, __( 'View original', 'wpml-translation-management' ), $job, $element_type_prefix, $original_element_type );

			            echo "<br/>";
			            echo $tm_post_link;
		            }
		            ?>
	            </td>
	            <td>
		            <?php
		            if($job->translator_id && $job->status == ICL_TM_WAITING_FOR_TRANSLATOR) {
			            ?>
			            <div class="icl_tj_your_job" title="<?php echo esc_html( __( 'This job is assigned specifically to you.', 'wpml-translation-management' ) ) ?>">!</div>
		            <?php
		            }
		            ?>
	            </td>
                <td><?php 
                    echo $iclTranslationManagement->status2text($job->status);
                    if($job->needs_update) {
	                    _e(' - (needs update)', 'wpml-translation-management');
                    }
                ?>
                </td>
                <td align="right">
                    <?php
                    if($job->translator_id > 0 && ($job->status == ICL_TM_WAITING_FOR_TRANSLATOR || $job->status == ICL_TM_IN_PROGRESS)){
	                    ?>
                    <a href="<?php echo admin_url('admin.php?page='.WPML_TM_FOLDER.'/menu/translations-queue.php&icl_tm_action=save_translation&resign=1&job_id='.$job->job_id) ?>" onclick="if(!confirm('<?php echo esc_js(__('Are you sure you want to resign from this job?', 'wpml-translation-management')) ?>')) {return false;}"><?php _e('Resign', 'wpml-translation-management')?></a>
                    <?php
                    } else {
	                    ?>
	                    &nbsp;
                    <?php
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>    
    </table>    
    
    <div class="tablenav">    
        <?php if ( $page_links ) { ?>
        <div class="tablenav-pages">
            <?php echo $page_links_text; ?>
        </div>
        <?php } ?>

	    <?php do_action("WPML_xliff_select_actions", $actions, "action2" ); ?>
    </div>    
    <?php // pagination - end ?>
    
    <?php if(sizeof($actions)>0): ?>
        </form>
    <?php endif; ?>
    
    
    <?php endif; ?>
    
</div>

<?php 
    // Check for any bulk actions
    if ( isset( $_POST[ 'action' ] ) || isset( $_POST[ "action2" ] ) ) {

	    $xliff_version = isset( $_POST[ 'doaction' ] ) ? $_POST[ 'action' ] : $_POST[ 'action2' ];
        do_action('WPML_translation_queue_do_actions_export_xliff', $_POST, $xliff_version );

    }
?>
    
