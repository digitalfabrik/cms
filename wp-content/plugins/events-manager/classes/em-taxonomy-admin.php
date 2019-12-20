<?php
/**
 * Base class for taxonomies, adding extra features to the admin area such as displaying and saving image and color options.
 * 
 * Classes extending this one must define the right  
 */
class EM_Taxonomy_Admin {
	
	/**
	 * The name of this taxonomy, e.g. event-categories, which is defined in child class.
	 * @var string
	 */
	public static $taxonomy_name;
	/**
	 * The name of the child class, used for now whilst late static binding isn't guaranteed since we may be running on PHP <5.3
	 * Once PHP 5.3 is a minimum requirement in WP, we can get rid of this one. 
	 * @var string
	 */
	public static $this_class = 'EM_Taxonomy_Admin';
	/**
	 * Currently used to instantiate a class of the specific term. Eventually we could just use EM_Taxonomy since these will be standardized functions for any taxonomy.
	 * @var string
	 */
	public static $tax_class = 'EM_Taxonomy';
	/**
	 * Name of taxonomy for reference in saving to database, e.g. category will be used to save category-image.
	 * This may differ from the name of the taxonomy, such as event-category can be category
	 * @var string
	 */
	public static $option_name = 'taxonomy';
	public static $name_singular = 'taxonomy';
	public static $name_plural = 'taxonomies';
	public static $placeholder_image = '#_TAXONOMYIMAGE';
	public static $placeholder_color = '#_TAXONOMYCOLOR';
		
	public static function init(){
		global $pagenow;
		if( ($pagenow == 'edit-tags.php' || $pagenow == 'term.php') && !empty($_GET['taxonomy']) && $_GET['taxonomy'] == self::$taxonomy_name){
			add_filter('admin_enqueue_scripts', 'EM_Taxonomy_Admin::admin_enqueue_scripts');
		}
		add_action( self::$taxonomy_name.'_edit_form_fields', array(self::$this_class, 'form_edit'), 10, 1);
		add_action( self::$taxonomy_name.'_add_form_fields', array(self::$this_class, 'form_add'), 10, 1);
		add_action( 'edited_'.self::$taxonomy_name, array(self::$this_class, 'save'), 10, 2);
		add_action( 'create_'.self::$taxonomy_name, array(self::$this_class, 'save'), 10, 2);
		add_action( 'delete_'.self::$taxonomy_name, array(self::$this_class, 'delete'), 10, 2);
		
		add_filter('manage_edit-'.self::$taxonomy_name.'_columns' , array(self::$this_class, 'columns_add'));
		add_filter('manage_'.self::$taxonomy_name.'_custom_column' , array(self::$this_class, 'columns_output'),10,3);
		
	}

	public static function columns_add($columns) {
		//prepend ID after checkbox
	    $columns['term-id'] = __('ID','events-manager');
	    return $columns;
	}
	
	public static function columns_output( $val, $column, $term_id ) {
		switch ( $column ) {
			case 'term-id':
				return $term_id;
				break;
		}
		return $val;
	}
	
	public static function admin_enqueue_scripts(){
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'em-taxonomies-admin', plugins_url().'/events-manager/includes/js/taxonomies-admin.js', array('jquery','media-upload','thickbox','farbtastic','wp-color-picker') );
	}
	
	public static function form_edit($tag){
		$EM_Taxonomy = new self::$tax_class($tag);
		$taxonomy_color = $EM_Taxonomy->get_color();
		$taxonomy_image = $EM_Taxonomy->get_image_url();
		$taxonomy_image_id = $EM_Taxonomy->get_image_id();
		?>
	    <tr class="form-field term-color-wrap">
	        <th scope="row" valign="top"><label for="term-color"><?php esc_html_e('Color','events-manager'); ?></label></th>
	        <td>
	            <input type="text" name="term_color" id="term-color" class="term-color" value="<?php echo esc_attr($taxonomy_color); ?>" /><br />
	            <p class="description"><?php echo sprintf(__('Choose a color for your %s. You can access this using the %s placeholder.','events-manager'), __(self::$name_singular, 'events-manager'), '<code>'. self::$placeholder_color. '</code>'); ?></p>
	            <div id="picker" style="position:absolute; display:none; background:#DEDEDE"></div>
	        </td>
	    </tr>
	    <tr class="form-field term-image-wrap">
	        <th scope="row" valign="top"><label for="term-image"><?php esc_html_e('Image','events-manager'); ?></label></th>
	        <td>
	        	<div class="img-container">
	        		<?php if( !empty($taxonomy_image) ): ?>
	        		<img src="<?php echo $taxonomy_image; ?>" />
	        		<?php endif; ?>
	        	</div>
	            <input type="text" name="term_image" id="term-image" class="img-url" value="<?php echo esc_attr($taxonomy_image); ?>" />
	            <input type="hidden" name="term_image_id" id="term-image-id" class="img-id" value="<?php echo esc_attr($taxonomy_image_id); ?>" />
	            <p class="hide-if-no-js">
		            <input id="upload_image_button" type="button" value="<?php _e('Choose/Upload Image','events-manager'); ?>" class="upload-img-button button-secondary" />
		            <input id="delete_image_button" type="button" value="<?php _e('Remove Image','events-manager'); ?>" class="delete-img-button button-secondary" <?php if( empty($taxonomy_image) ) echo 'style="display:none;"'; ?> />
				</p>
	            <br />
				<p class="description"><?php echo sprintf(__('Choose an image for your %s, which can be displayed using the %s placeholder.','events-manager'), __(self::$name_singular,'events-manager'),'<code>'. self::$placeholder_image. '</code>'); ?></p>
	        </td>
	    </tr>
	    <?php
	}
	
	public static function form_add(){
		?>
	    <div class="term-color-wrap">
	        <label for="term-color"><?php esc_html_e('Color','events-manager'); ?></label>
            <input type="text" name="term_color" id="term-color" class="term-color" value="#FFFFFF" /><br />
            <p class="description"><?php echo sprintf(__('Choose a color for your %s. You can access this using the %s placeholder.','events-manager'), __(self::$name_singular,'events-manager'),'<code>'. self::$placeholder_color. '</code>'); ?></p>
	    </div>
	    <div class="form-field term-image-wrap">
	        <label for="term-image"><?php esc_html_e('Image','events-manager'); ?></label>
        	<div class="img-container"></div>
            <input type="text" name="term_image" id="term-image" class="img-url" value="" />
            <input type="hidden" name="term_image_id" id="term-image-id" class="img-id" value="" />
            <p class="hide-if-no-js">
	            <input id="upload_image_button" type="button" value="<?php _e('Choose/Upload Image','events-manager'); ?>" class="upload-img-button button-secondary" />
	            <input id="delete_image_button" type="button" value="<?php _e('Remove Image','events-manager'); ?>" class="delete-img-button button-secondary" style="display:none;" />
			</p>
			<p class="description"><?php echo sprintf(__('Choose an image for your %s, which can be displayed using the %s placeholder.','events-manager'), __(self::$name_singular,'events-manager'),'<code>'. self::$placeholder_image. '</code>'); ?></p>
	    </div>
	    <?php
	}
	
	public static function save( $term_id, $tt_id ){
		global $wpdb;
	    if (!$term_id) return;
		if( !empty($_POST['term_color']) ){
			//get results and save/update
			$color = sanitize_hex_color($_POST['term_color']);
			if( $color ){
				$prev_settings = $wpdb->get_results('SELECT meta_value FROM '.EM_META_TABLE." WHERE object_id='{$term_id}' AND meta_key='". self::$option_name ."-bgcolor'");
				if( count($prev_settings) > 0 ){
					$wpdb->update(EM_META_TABLE, array('object_id' => $term_id, 'meta_value' => $color), array('object_id' => $term_id, 'meta_key' => self::$option_name .'-bgcolor'));
				}else{
					$wpdb->insert(EM_META_TABLE, array('object_id' => $term_id, 'meta_key' => self::$option_name .'-bgcolor', 'meta_value' => $color));
				}
			}
		}
		if( !empty($_POST['term_image']) ){
			//get results and save/update
			$term_image = esc_url_raw($_POST['term_image']);
			$prev_settings = $wpdb->get_results('SELECT meta_value FROM '.EM_META_TABLE." WHERE object_id='{$term_id}' AND meta_key='". self::$option_name ."-image'");
			if( count($prev_settings) > 0 ){
				$wpdb->update(EM_META_TABLE, array('object_id' => $term_id, 'meta_value' => $term_image), array('object_id' => $term_id, 'meta_key' => self::$option_name .'-image'));
			}else{
				$wpdb->insert(EM_META_TABLE, array('object_id' => $term_id, 'meta_key' => self::$option_name .'-image', 'meta_value' => $term_image));
			}
			if( !empty($_POST['term_image_id']) && is_numeric($_POST['term_image_id']) ){
				//get results and save/update
				$term_image_id = absint($_POST['term_image_id']);
				$prev_settings = $wpdb->get_results('SELECT meta_value FROM '.EM_META_TABLE." WHERE object_id='{$term_id}' AND meta_key='". self::$option_name ."-image-id'");
				if( count($prev_settings) > 0 ){
					$wpdb->update(EM_META_TABLE, array('object_id' => $term_id, 'meta_value' => $term_image_id), array('object_id' => $term_id, 'meta_key'=> self::$option_name .'-image-id'));
				}else{
					$wpdb->insert(EM_META_TABLE, array('object_id' => $term_id, 'meta_key'=> self::$option_name .'-image-id', 'meta_value' => $term_image_id));
				}
			}
		}else{
			//check if an image exists, if so remove association
			$prev_settings = $wpdb->get_results('SELECT meta_value FROM '.EM_META_TABLE." WHERE object_id='{$term_id}' AND meta_key='". self::$option_name ."-image'");
			if( count($prev_settings) > 0 ){
				$wpdb->delete(EM_META_TABLE, array('object_id' => $term_id, 'meta_key' => self::$option_name .'-image'));
				$wpdb->delete(EM_META_TABLE, array('object_id' => $term_id, 'meta_key' => self::$option_name .'-image-id'));
			}
		}
	}
	
	public static function delete( $term_id ){
		global $wpdb;
		//delete taxonomy image and color
		$wpdb->query('DELETE FROM '.EM_META_TABLE." WHERE object_id='$term_id' AND (meta_key='". self::$option_name ."-image' OR meta_key='". self::$option_name ."-image-id' OR meta_key='". self::$option_name ."-bgcolor')");
		//delete all events taxonomy relations for MultiSite Global Mode
		if( EM_MS_GLOBAL ){
			$wpdb->query('DELETE FROM '.EM_META_TABLE." WHERE meta_value='{$term_id}' AND meta_key='event-". self::$option_name ."'");
		}
	}
}