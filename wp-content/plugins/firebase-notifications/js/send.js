jQuery(document).ready(function() {

	jQuery(".pn-title").simplyCountable({
        maxCount: 50,
		counter: '.counter'
	});

	jQuery('.pn-message').simplyCountable({
		maxCount: 140,
		counter: '.counter2'
	});

	window.location.href = "#deflang";

	
});
