<h3>Tour <i><?php echo $tour_name->cg_name; ?></i> l&ouml;schen</h3>

<?php require_once dirname( dirname( __FILE__ ) ) . '/action/deleteTour.php'; // form action ?>

<div class="deletetour">
	<p>Best&auml;tigen Sie, dass Sie die Tour l&ouml;schen m&ouml;chten:</p>

	<form action="<?php echo $_SERVER['PHP_SELF']; ?>?page=clickguide-optionspage&tab=deletetour&tour=<?php echo $_GET['tour'] ?>" method="post">
		<input type="hidden" name="delete" />
		
		<input class="button button-primary" type="submit" value="Tour löschen" />
		<a href="?page=clickguide-optionspage&tab=tours" class="abortDeletion">L&ouml;schvorgang abbrechen</a>
	</form>
</div>