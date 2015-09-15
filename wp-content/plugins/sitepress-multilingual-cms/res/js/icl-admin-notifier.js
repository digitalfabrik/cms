/* <![CDATA[*/
jQuery(document).ready(function () {
    jQuery('a.icl-admin-message-hide').live('click', function (event) {

		if (typeof(event.preventDefault) !== 'undefined' ) {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}

		var messagebox = jQuery(this).parent().parent();


        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'icl-hide-admin-message',
                'icl-admin-message-id': jQuery(this).parent().parent().attr('id')
            },
            dataType: 'json',
            success: function (ret) {

                if (ret) {
                    messagebox.fadeOut('slow', function() {
                        messagebox.removeAttr('class');
                        if(ret.type) messagebox.addClass(ret.type);
                        messagebox.html(ret.text);
                        messagebox.fadeIn();
                    });
                } else {
                    messagebox.fadeOut();
                }
            }
        });
    });
	jQuery('a.icl-admin-message-link').live('click', function (event) {

		if (typeof(event.preventDefault) !== 'undefined' ) {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}

		jQuery.post(
			ajaxurl,
			{
				action: 'icl-hide-admin-message',
				'icl-admin-message-id': jQuery(this).parent().parent().attr('id')
			},
			function (response) {
			}
		);
	});
});
/*]]>*/