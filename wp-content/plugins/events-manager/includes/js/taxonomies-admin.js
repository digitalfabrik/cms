jQuery(document).ready(function($) {
	//other color picker
	$('.term-color').wpColorPicker();

	//Event Taxonomy Image Picker
	var frame;	
	// ADD IMAGE LINK
	$('.term-image-wrap .upload-img-button').on( 'click', function( event ){
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
			$( '.term-image-wrap .img-container').empty().append( '<img src="'+attachment.url+'" alt="" style="max-width:100%;"/>' );
			// Send the attachment id to our hidden input
			$( '.term-image-wrap .img-id' ).val( attachment.id );
			$( '.term-image-wrap .img-url' ).val( attachment.url );
			// Unhide the remove image link
			$( '.term-image-wrap .delete-img-button').show();
		});
		// Finally, open the modal on click
		frame.open();
	});
	// DELETE IMAGE LINK
	$( '.term-image-wrap .delete-img-button').on( 'click', function( event ){
		event.preventDefault();
		// Clear out the preview image
		$( '.term-image-wrap .img-container').html( '' );
		// Un-hide the add image link
		$(this).hide();
		// Delete the image id from the hidden input
		$( '.term-image-wrap .img-id' ).val( '' );
		$( '.term-image-wrap .img-url' ).val( '' );
	});

});