/*global $:false */

jQuery(document).ready(function($){'use strict';

	$('.nav.navbar-nav').onePageNav({
		currentClass: 'active',
	    changeHash: false,
	    scrollSpeed: 900,
	    scrollOffset: 60,
	    scrollThreshold: 0.3,
	    filter: ':not(.no-scroll)'
	});

	var stickyNavTop = $('#masthead').offset().top;

   	var stickyNav = function(){
	    var scrollTop = $(window).scrollTop();

	    if (scrollTop > stickyNavTop) { 
	        $('#masthead').addClass('sticky');
	    } else {
	        $('#masthead').removeClass('sticky'); 
	    }
	};

	stickyNav();

	$(window).scroll(function() {
		stickyNav();
	});
});