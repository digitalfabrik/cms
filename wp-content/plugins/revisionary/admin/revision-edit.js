jQuery(document).ready( function($) {	
function updateTextRvy() {
$('#selected_timestamp_div').show();
$('#selected_timestamp').html(
	revL10n.unsavedDate + ' <b>' +
	$( '#mm option[value="' + $('#mm').val() + '"]' ).text() + ' ' +
	$('#jj').val() + ', ' +
	$('#aa').val() + ' @ ' +
	$('#hh').val() + ':' +
	$('#mn').val() + '</b> '
);

$('#rvy_revision_edit_secondary_div').show();
}

$('.edit-timestamp').click(function () {
if ($('#timestampdiv').is(":hidden")) {
	$('#timestampdiv').slideDown("normal");
	$('.edit-timestamp').hide();
	$('#rvy_revision_edit_secondary_div').hide();
}
return false;
});

$('.cancel-timestamp').click(function() {
$('#timestampdiv').slideUp("normal");
$('#mm').val($('#hidden_mm').val());
$('#jj').val($('#hidden_jj').val());
$('#aa').val($('#hidden_aa').val());
$('#hh').val($('#hidden_hh').val());
$('#mn').val($('#hidden_mn').val());
$('.edit-timestamp').show();
$('#rvy_revision_edit_secondary_div').show();
return false;
});

$('.save-timestamp').click(function () {
$('#timestampdiv').slideUp("normal");
$('.edit-timestamp').show();
updateTextRvy();
return false;
});
});

function tmCallbackRvy (el, content, body) {
	if ( tinyMCE.activeEditor.isHidden() )
		content = switchEditors.I(el).value;
	else
		content = switchEditors.pre_wpautop(content);

	return content;
}