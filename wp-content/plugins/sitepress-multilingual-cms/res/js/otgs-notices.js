/*globals jQuery, ajaxurl */

jQuery(document).ready(function () {
	'use strict';

	var otgsNotice = jQuery('.otgs-notice');

	var hideNotice = function (noticeBox) {
		if (noticeBox) {
			var noticeId = noticeBox.data('id');
			var noticeGroup = noticeBox.data('group');

			jQuery.ajax({
										url:      ajaxurl,
										type:     'POST',
										data:     {
											action:  'otgs-hide-notice',
											'id':    noticeId,
											'group': noticeGroup
										},
										dataType: 'json'
									});
		}
	};

	otgsNotice.on('click', '.notice-dismiss', function (event) {

		if (typeof(event.preventDefault) !== 'undefined') {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}

		var noticeBox = jQuery(this).closest('.is-dismissible');
		hideNotice(noticeBox);
	});

	otgsNotice.on('click', 'a.otgs-hide-link', function (event) {
		if (typeof(event.preventDefault) !== 'undefined') {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}

		var noticeBox = jQuery(this).closest('.is-dismissible');
		hideNotice(noticeBox);
	});
});