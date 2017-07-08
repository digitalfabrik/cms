(function (mobilePreview, $, undefined) { // namespace
	mobilePreview.postTitle = undefined;
	mobilePreview.postContent = undefined;
	mobilePreview.title = undefined;
	mobilePreview.content = undefined;


	mobilePreview.copyTitle = function () {
		var titleContent = mobilePreview.postTitle.val();
		mobilePreview.title.html(titleContent);
	};

	/**
	 * Copy text from the text editor
	 */
	mobilePreview.copyTextContent = function () {
		mobilePreviewWPAutoP(jQuery("#content").html());
	};
}(window.mobilePreview = window.mobilePreview || {}, jQuery));

function mobilePreviewWPAutoP( editorContent ){
	jQuery.post( "index.php", { 'mpvwpautop': editorContent })
	.done(function( data ) {
		mobilePreview.content.html( data );
	});
}

jQuery(document).ready(function () {
	mobilePreview.postTitle = jQuery('#title');
	mobilePreview.postContent = jQuery('#content');

	mobilePreview.title = jQuery('#mobile-preview-title');
	mobilePreview.content = jQuery('#mobile-preview-content');

	mobilePreview.postTitle.on("keyup", mobilePreview.copyTitle);
	mobilePreview.copyTitle();

	mobilePreview.postContent.on("keyup", mobilePreview.copyTextContent);
	mobilePreview.copyTextContent();
});


/**
 * Copy text from the tinyMCE editor
 * @param editor the tinyMCE editor
 */
function mobilePreviewCopyTinymceContent(editor) {
	var editorContent = editor.getContent();
	mobilePreview.content.html(editorContent);
}
