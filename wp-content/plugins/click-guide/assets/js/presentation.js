jQuery(document).ready(function() {

	welcomeBoxPosition();

 	closeBox();

	waypointBoxPosition();

});

function welcomeBoxPosition() {
	var left = jQuery('#welcomeBox').width() / 2 * (-1);
	jQuery('#welcomeBox').css('marginLeft',left);
}

function closeBox() {
	jQuery('.closeBox').click(function() {
		jQuery('.presentationBox').fadeOut(200);
	});
}

function waypointBoxPosition() {
	if( jQuery('#waypointBox').length ) {
		setTimeout(function() {
			var waypointBox = jQuery('#waypointBox');

			var waypointPosition = waypointBox.data('position');

			var top = jQuery(waypointPosition).offset().top;
			var left = jQuery(waypointPosition).offset().left;
			var right = left + jQuery(waypointPosition).outerWidth();

			if (left + waypointBox.width() > jQuery(document).width()) {
				waypointBox.css('left', left - waypointBox.width() - 25);
				jQuery('.waypointBoxArrow.right').show();
			} else {
				waypointBox.css('left', right + 25);
				jQuery('.waypointBoxArrow.left').show();
			}

			if (top == 0) {
				waypointBox.css('top', 5);
			} else {
				waypointBox.css('top', top);
			}

			if (waypointBox.offset().top > jQuery(window).height() - waypointBox.height()) {
				jQuery('html,body').animate({
					scrollTop: waypointBox.offset().top + waypointBox.height()
				}, 600);
			}
		}, 100);
	}
}