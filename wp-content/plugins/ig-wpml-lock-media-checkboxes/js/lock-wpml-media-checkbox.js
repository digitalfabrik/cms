jQuery(document).ready(function () {
	jQuery('input[name=icl_duplicate_attachments]').prop('checked', true);
	jQuery('input[name=icl_duplicate_attachments]').click(function(){return false;});
	jQuery('input[name=icl_duplicate_featured_image]').prop('checked', true);
	jQuery('input[name=icl_duplicate_featured_image]').click(function(){return false;});
});
