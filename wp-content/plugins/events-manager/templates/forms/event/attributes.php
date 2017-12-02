<?php
global $EM_Event;
$attributes = em_get_attributes();
$has_deprecated = false;
?>
<div id="event-attributes">
	<?php if( !empty($attributes['names']) && count( $attributes['names'] ) > 0 ) : ?>
		<table class="form-table">
			<thead>
				<tr valign="top">
					<td><strong>Attribute Name</strong></td>
					<td><strong>Value</strong></td>
				</tr>
			</thead> 
			<tbody id="mtm_body">
				<?php
				$count = 1;
				foreach( $attributes['names'] as $name){
					?>
					<tr valign="top" id="em_attribute_<?php echo $count ?>">
						<td scope="row"><?php echo $name ?></td>
						<td>
							<?php if( count($attributes['values'][$name]) > 1 ): ?>
							<select name="em_attributes[<?php echo $name ?>]">
								<?php foreach($attributes['values'][$name] as $attribute_val): ?>
									<?php if( array_key_exists($name, $EM_Event->event_attributes) && $EM_Event->event_attributes[$name]==$attribute_val ): ?>
										<option selected="selected"><?php echo $attribute_val; ?></option>
									<?php else: ?>
										<option><?php echo $attribute_val; ?></option>
									<?php endif; ?>
								<?php endforeach; ?>
							</select>
							<?php else: ?>
							<input type="text" name="em_attributes[<?php echo $name ?>]" value="<?php echo array_key_exists($name, $EM_Event->event_attributes) ? esc_attr($EM_Event->event_attributes[$name], ENT_QUOTES):''; ?>" />
							<?php endif; ?>
						</td>
					</tr>
					<?php
					$count++;
				}
				if($count == 1){
					?>
					<tr><td colspan="2"><?php echo sprintf(__("You don't have any custom attributes defined in any of your Events Manager template settings. Please add them the <a href='%s'>settings page</a>",'events-manager'),EM_ADMIN_URL ."&amp;page=events-manager-options"); ?></td></tr>
					<?php
				}
				?>
			</tbody>
		</table>
	<?php else : ?>
		<p>
		<?php _e('In order to use attributes, you must define some in your templates, otherwise they\'ll never show. Go to Events > Settings > General to add attribute placeholders.', 'events-manager'); ?>
		</p> 
		<script>
			jQuery(document).ready(function($){ $('#event_attributes').addClass('closed'); });
		</script>
	<?php endif; ?>
</div>