var WPML_Media_Posts_Media_Flag = WPML_Media_Posts_Media_Flag || {};

jQuery(function ($) {

    "use strict";

    var updateContainer = $("#wpml-media-posts-media-flag");
    var updateButton = updateContainer.find(".button-primary");
    var spinner = updateContainer.find(".spinner");
    var nonce = updateContainer.find("input[name=nonce]").val();
    var statusContainer = updateContainer.find(".status");

    function getQueryParams(qs) {
        qs = qs.split('+').join(' ');

        var params = {},
            tokens,
            re = /[?&]?([^=]+)=([^&]*)/g;

        while (tokens = re.exec(qs)) {
            params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
        }

        return params;
    }


    var queryParams = getQueryParams(location.search);
    if (queryParams.run_setup) {
        showProgress();
        runSetup();
    }

    updateButton.on("click", function () {
        showProgress();
        runSetup();
    });

    function showProgress() {
        spinner.css({visibility: "visible"});
        updateButton.prop("disabled", true);
    }

    function hideProgress() {
        spinner.css({visibility: "hidden"});
        updateButton.prop("disabled", false);
    }

    function setStatus(statusText) {
        statusContainer.html(statusText);
    }

    function runSetup() {
        var data = {
            action: "wpml_media_set_has_media_flag_prepare",
            nonce: nonce
        };
        $.ajax({
            url: ajaxurl,
            type: "POST",
            dataType: "json",
            data: data,
            success: function (response) {
                if (response.data.status) {
                    setStatus(response.data.status);
                }
                setInitialLanguage();
            }
        });
    }

    function setInitialLanguage() {
        var data = {
            action: "wpml_media_set_initial_language",
            nonce: nonce
        };
        $.ajax({
            url: ajaxurl,
            type: "POST",
            dataType: "json",
            data: data,
            success: function (response) {
				var message = response.message ? response.message : response.data.message;
                setStatus( message );
                setHasMediaFlag(0);
            }
        });
    }

    function setHasMediaFlag(offset) {
        var data = {
            action: "wpml_media_set_has_media_flag",
            nonce: nonce,
            offset: offset
        };
        $.ajax({
            url: ajaxurl,
            type: "POST",
            dataType: "json",
            data: data,
            success: function (response) {
                if (response.data.status) {
                    setStatus(response.data.status);
                }
                if (response.data.continue) {
                    setHasMediaFlag(response.data.offset);
                } else {
                    if (queryParams.redirect_to) {
                        location.href = queryParams.redirect_to;
                    } else {
                        location.reload();
                    }
                }
            }
        });
    }

});
