jQuery(document).ready(function($) {
	//color picker
	var f = $.farbtastic('#picker');
	var p = $('#picker').css('opacity', 0.25);
	var selected;
	$('.colorwell').each(function () { f.linkTo(this); $(this).css('opacity', 0.75); }).focus(function() {
		if (selected) { $(selected).css('opacity', 0.75).removeClass('colorwell-selected'); }
		f.linkTo(this);
		p.css('opacity', 1);
		$(selected = this).css('opacity', 1).addClass('colorwell-selected');
	});
	$('.colorwell').click(function() {
		var position = $(selected = this).position();
		$('#picker').css('left', (position.left + 150) );
		$('#picker').css('top', position.top); 
		$('#picker').fadeIn(900,function (){
			$('#picker').css('display', 'inline');
		});
	}).blur(function(){
		$('#picker').fadeOut('fast');
		$('#picker').css('display', 'none');
	});

	//Event Taxonomy Image Picker
	var frame;	
	// ADD IMAGE LINK
	$('#event-tax-image .upload-img-button').on( 'click', function( event ){
		event.preventDefault();
		// If the media frame already exists, reopen it.
		if ( frame ) {
		frame.open();
		return;
		}
		// Create a new media frame
		frame = wp.media({
			library: {
				type: 'image'
			},
			title: wp.media.view.l10n.chooseImage,
			multiple: false  // Set to true to allow multiple files to be selected
		});
		// When an image is selected in the media frame...
		frame.on( 'select', function() {
			// Get media attachment details from the frame state
			var attachment = frame.state().get('selection').first().toJSON();
			// Send the attachment URL to our custom image input field.
			$( '#event-tax-image .img-container').empty().append( '<img src="'+attachment.url+'" alt="" style="max-width:100%;"/>' );
			// Send the attachment id to our hidden input
			$( '#event-tax-image .img-id' ).val( attachment.id );
			$( '#event-tax-image .img-url' ).val( attachment.url );
			// Unhide the remove image link
			$( '#event-tax-image .delete-img-button').show();
		});
		// Finally, open the modal on click
		frame.open();
	});
	// DELETE IMAGE LINK
	$( '#event-tax-image .delete-img-button').on( 'click', function( event ){
		event.preventDefault();
		// Clear out the preview image
		$( '#event-tax-image .img-container').html( '' );
		// Un-hide the add image link
		$(this).hide();
		// Delete the image id from the hidden input
		$( '#event-tax-image .img-id' ).val( '' );
		$( '#event-tax-image .img-url' ).val( '' );
	});

});