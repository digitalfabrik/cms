jQuery(document).ready(function($){'use strict',

	$('#post-formats-select input').change(checkFormate);

	function checkFormate(){

		var formate = $('#post-formats-select input:checked').attr('value');

		if(typeof formate != 'undefined'){
			$('div[id^=post-meta-]').hide();
			$('div[id^=post-meta-'+formate+']').stop(true,true).fadeIn(600);
		}
	}

	$(window).load(function(){'use strict',
		checkFormate();
	})

});