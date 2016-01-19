<h3>Neue Tour erstellen</h3>

<?php require_once dirname( dirname( __FILE__ ) ) . '/action/createNewTour.php'; // form action ?>

<div class="formNewTour">
	<form action="<?php echo $_SERVER['PHP_SELF']; ?>?page=clickguide-optionspage&tab=tours" method="post">
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">Name</th>
					<td>
						<input type="text" name="tourName" />
					</td>
				</tr>
				<tr>
					<th scope="row">Beschreibung</th>
					<td>
						<textarea name="tourDesc" placeholder="Diese Bechreibung dient nur internen Zewck und wird dem Nutzer nicht angezeigt."></textarea>
					</td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="createNewTour" />
		<p><input class="button button-primary" type="submit" value="Tour erstellen" /></p>
	</form>
</div>