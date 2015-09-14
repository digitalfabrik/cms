<?php 
$icl_enabled_packages = $WPML_Packages->get_enabled_packages();
?>
<div class="wrap">
    <div id="icon-wpml" class="icon32"><br /></div>
    <h2><?php echo __('Setup WPML', 'sitepress') ?></h2>    
    
    <p class="error fade" style="padding:10px;"><?php _e('WPML compatibility packages will soon be obsolete and removed. Please use other means to make your site multilingual-ready.', 'sitepress')?></p>
    
    <h3><?php echo __('Compatibility packages', 'sitepress') ?></h3>    
    <?php
		$post_icl_packages   = filter_input(INPUT_POST, 'icl_packages', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);

		foreach($WPML_Packages->get_packages() as $package_type => $packages): ?>
    <h4><?php echo ucfirst($package_type) ?></h4>
    
    
    
    <form action="" method="post">
        <?php if(isset($post_icl_packages[$package_type])): ?>
        <div class="icl_form_success">
        <?php _e('Packages list updated', 'sitepress'); ?>
        </div>
        <?php endif; ?>        
        
        <?php if(!empty($packages)): ?>
        <table id="icl_packages_<?php echo $package_type ?>" class="widefat">
            <thead>
                <tr>
                    <th class="manage-column column-cb check-column" scope="col">&nbsp;</th>
                    <th><?php _e('Package name', 'sitepress') ?></th>
                    <th><?php _e('Version', 'sitepress') ?></th>
                    <th><?php _e('Description', 'sitepress') ?></th>
                    <?php if($package_type=='themes'):?>
                    <th><?php _e('Used for theme', 'sitepress') ?></th>
                    <?php endif; ?>
                    <?php if($package_type=='plugins'):?>
                    <th><?php _e('Used for plugin', 'sitepress') ?></th>
                    <?php endif; ?>                
                    <th><?php _e('Author', 'sitepress') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php $incr = 0; ?>
                <?php foreach($packages as $package_id => $package):?>
                <?php 
                    $incr++;
                    if($icl_enabled_packages[$package_type][$package_id]){
                        $checked = ' checked="checked"';
                    }else{
                        $checked = '';
                    }
                ?>
                <tr <?php if($incr%2==0): ?>class="alternate"<?php endif?>>
                    <td>
                        <input type="hidden" name="icl_packages[<?php echo $package_type ?>][<?php echo $package_id ?>]" value="0" />
                        <input type="checkbox" name="icl_packages[<?php echo $package_type ?>][<?php echo $package_id ?>]" value="1"<?php echo $checked; ?> />
                    </td>
                    <td><?php if($package['URI']) echo '<a href="'.$package['URI'].'">'; echo $package['Name']; if($package['URI']) echo '</a>';?></td>
                    <td><?php echo $package['Version'];?></td>
                    <td><?php echo $package['Description'];?></td>
                    <?php if($package_type=='themes'):?>
                    <td><?php echo $package['Theme']; if($package['ThemeVersion']) echo ' ('.$package['ThemeVersion'].')'; ?></td>
                    <?php endif; ?>
                    <?php if($package_type=='plugins'):?>
                    <td><?php echo $package['Plugin']; if($package['PluginVersion']) echo ' ('.$package['PluginVersion'].')'; ?></td>
                    <?php endif; ?>
                    <td><?php if($package['AuthorURI']) echo '<a href="'.$package['AuthorURI'].'">'; echo $package['Author']; if($package['AuthorURI']) echo '</a>';?></td>                                    
                </tr>
                <?php endforeach; ?>            
            </tbody>
            <tfoot>
                <tr>
                    <th class="manage-column column-cb check-column" scope="col">&nbsp;</th>
                    <th><?php _e('Package name', 'sitepress') ?></th>
                    <th><?php _e('Version', 'sitepress') ?></th>
                    <th><?php _e('Description', 'sitepress') ?></th>
                    <?php if($package_type=='themes'):?>
                    <th><?php _e('Used for theme', 'sitepress') ?></th>
                    <?php endif; ?>
                    <?php if($package_type=='plugins'):?>
                    <th><?php _e('Used for plugin', 'sitepress') ?></th>
                    <?php endif; ?>                

                    <th><?php _e('Author', 'sitepress') ?></th>
                </tr>
            </tfoot>        
        </table>
        <?php else: ?>
            <div style="text-align:center"><?php _e('No packages found', 'sitepress'); ?></div>  
        <?php endif; ?>
        
        <?php if(!empty($packages)): ?>
        <p class="submit alignright"><input type="submit" value="<?php _e('Update', 'sitepress'); ?>" /></p>
        <br clear="all" />
        <?php endif; ?>
    </form>
    
    <?php endforeach; ?>
    
</div>