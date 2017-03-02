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
		var editorContent = mobilePreview.postContent.text();
		// replace all \n with surrounding p tags
		// split-join is faster than replace: http://jsperf.com/replace-all-vs-split-join
		editorContent = editorContent.replace("\n","<br>");
		mobilePreview.content.html(editorContent);
	};
}(window.mobilePreview = window.mobilePreview || {}, jQuery));


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
