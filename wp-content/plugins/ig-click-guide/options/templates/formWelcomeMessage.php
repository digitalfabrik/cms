<?php require_once dirname( dirname( __FILE__ ) ) . '/action/saveWelcomeMessage.php'; // form action ?>

<?php if(isset($_GET['updated'])) : ?>
	<div id="message" class="updated fade">
		<p><?php _e( 'Settings Saved', 'my' ) ?></p>
	</div>
<?php endif; ?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=clickguide-optionspage">
<?php 
    settings_fields( 'welcome_popup_group' );   
    do_settings_sections( 'clickguide-optionspage-welcomemessage' );
    submit_button();
?>
</form>