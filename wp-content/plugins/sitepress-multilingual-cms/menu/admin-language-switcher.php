<?php /* within Sitepress scope */  ?>

<?php global $wp_version; ?>

<span id="icl-als-wrap" <?php if($this->is_rtl()):?>style="direction:rtl;float:right;"<?php elseif(version_compare($wp_version, '3.3', '>=')):?>style="float:left;position:absolute;z-index:10;"<?php endif; ?>>

    <?php if($this->is_rtl()):?>
    <div id="icl-als-info" style="margin-right:8px;">
    <?php icl_pop_info(sprintf(__('This language selector determines which content to display. You can choose items in a specific language or in all languages. To change the language of the WordPress Admin interface, go to <a%s>your profile</a>.', 'sitepress'), ' href="'.admin_url('profile.php').'"'), 'question'); ?>
    </div>
    
    <?php endif; ?>
    

    <div id="icl-als-actions-label" <?php if($this->is_rtl()):?>style="float:right;"<?php endif; ?>><?php _e('Show content in:', 'sitepress'); ?></div>
    <div id="icl-als-actions">
            <?php foreach($langlinks as $link): if($link['current']): ?>
            <?php if($this->is_rtl()) $link['flag'] = str_replace('<img ', '<img style="float:right;margin-right:0;margin-left:4px;" ', $link['flag']); ?>
            <div id="icl-als-first"><a href="<?php echo $link['url'] ?>"><?php echo $link['flag'] ?><?php echo $link['anchor'] ?></a></div>
            <?php endif; endforeach; ?>
            <div id="icl-als-toggle"><br /></div>
            <div id="icl-als-inside">
                <?php foreach($langlinks as $link): if(!$link['current']):?>
                    <?php if($this->is_rtl()) $link['flag'] = str_replace('<img ', '<img style="float:right;margin-right:0;margin-left:4px;" ', $link['flag']); ?>
                    <div class="icl-als-action"><a href="<?php echo $link['url'] ?>"><?php echo $link['flag'] ?><?php echo $link['anchor'] ?></a></div>
                <?php endif; endforeach; ?>
            </div>
    </div>

    <?php if(!$this->is_rtl()):?>
    <div id="icl-als-info">
    <?php icl_pop_info(sprintf(__('This language selector determines which content to display. You can choose items in a specific language or in all languages. To change the language of the WordPress Admin interface, go to <a%s>your profile</a>.', 'sitepress'), ' href="'.admin_url('profile.php').'"'), 'question'); ?>
    </div>
    <?php endif; ?>

</span>