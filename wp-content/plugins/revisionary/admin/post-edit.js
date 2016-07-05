jQuery(document).ready( function($) {
	$('#misc-publishing-actions div.num-revisions').contents().filter(function() {
     if ( typeof(this.nodeValue) != 'undefined' ){ console.debug(this.nodeValue);} return ( this.nodeType == 3 && ( typeof(this.nodeValue) != 'undefined' ) && this.nodeValue.indexOf("Revisions") != -1 ); }).wrap('<span class="rev-caption"></span>');
	 
	 $('#misc-publishing-actions span.rev-caption').html('Publication History:&nbsp;');
});