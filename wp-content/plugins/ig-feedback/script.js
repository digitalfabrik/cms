jQuery(document).ready(function(){
  jQuery('.ig-feedback-spoiler-show, .ig-feedback-spoiler-hide').click(function(event){
      event.preventDefault();
      jQuery("[data-ig-feedback-comment-id='" + jQuery(this).data('ig-feedback-comment-id') + "']").toggle('fast');
  })
});
