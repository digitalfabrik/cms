/*jshint browser:true, devel:true */
/*globals jQuery, ajaxurl*/
var WPMLTranslationServicesDialog = function () {
	"use strict";

	var self = this;

	self.preventEventDefault = function (event) {
		if ('undefined' !== event && 'undefined' !== typeof(event.preventDefault)) {
			event.preventDefault();
		} else {
			event.returnValue = false;
		}
	};

	self.enterKey = 13;
	self.ajaxSpinner = jQuery('<span class="spinner"></span>');
	self.activeService = jQuery( '.js-ts-active-service' );

	self.init = function () {

		var invalidateServiceLink;
		var authenticateServiceLink;
		var deactivateServiceLink;
		var activateServiceLink;
		var activateServiceImage;
		var flushWebsiteDetailsCacheLink;
		var header;
		var tip;

		header = self.activeService.find( '.active-service-header' ).val();
		tip = self.activeService.find( '.active-service-tip' ).val();

		self.serviceDialog = jQuery('<div id="service_dialog"><h4>' + header + '</h4><div class="custom_fields_wrapper"></div><i>' + tip + '</i><br /><br /><div class="tp_response_message icl_ajx_response"></div>');
		self.customFieldsSerialized = jQuery('#custom_fields_serialized');
		self.ajaxSpinner.addClass('is-active');

		activateServiceImage = jQuery('.js-activate-service');
		activateServiceLink = jQuery('.js-activate-service-id');
		deactivateServiceLink = jQuery('.js-deactivate-service');
		authenticateServiceLink = jQuery('.js-authenticate-service');
		invalidateServiceLink = jQuery('.js-invalidate-service');
		flushWebsiteDetailsCacheLink = jQuery('.js-flush-website-details-cache');

		activateServiceImage.bind('click', function (event) {
			var link;
			self.preventEventDefault(event);

			link = jQuery(this).closest('li').find('.js-activate-service-id');
			link.trigger('click');
			return false;
		});

		activateServiceLink.bind('click', function (event) {
			var serviceId;
			var button;
			self.preventEventDefault(event);

			button = jQuery(this);
			serviceId = jQuery(this).data('id');
			self.toggleService(serviceId, button, 1);

			return false;
		});

		deactivateServiceLink.bind('click', function (event) {
			var serviceId;
			var button;
			self.preventEventDefault(event);

			button = jQuery(this);
			serviceId = jQuery(this).data('id');
			self.toggleService(serviceId, button, 0);

			return false;
		});

		invalidateServiceLink.bind('click', function (event) {
			var serviceId;
			var button;
			self.preventEventDefault(event);

			button = jQuery(this);
			serviceId = jQuery(this).data('id');
			self.translationServiceAuthentication(serviceId, button, 1);

			return false;
		});

		flushWebsiteDetailsCacheLink.on('click', function (event) {
			var anchor = jQuery(this);
			self.preventEventDefault(event);

			self.flushWebsiteDetailsCache(anchor);

			return false;
		});

		authenticateServiceLink.bind('click', function (event) {
			var customFields;
			var serviceId;
			self.preventEventDefault(event);

			serviceId = jQuery(this).data('id');
			customFields = jQuery(this).data('custom-fields');

			self.serviceAuthenticationDialog(customFields, serviceId);

			return false;
		});
	};

	self.toggleService = function (serviceId, button, enableService) {
		var ajaxData;
		var enable = enableService;
		var nonce = jQuery( '.translation_service_toggle' ).val();
		if ('undefined' === typeof enableService) {
			enable = 0;
		}

		button.attr('disabled', 'disabled');
		button.after(self.ajaxSpinner);

		ajaxData = {
			'action':     'translation_service_toggle',
			'nonce':      nonce,
			'service_id': serviceId,
			'enable':     enable
		};

		jQuery.ajax({
			type:     "POST",
			url:      ajaxurl,
			data:     ajaxData,
			dataType: 'json',
			success:  function (response) {
				var data = response.data;

				if (data.reload) {
					location.reload(true);
				} else {
					if (button) {
						button.removeAttr('disabled');
						button.next().fadeOut();
					}
				}
			},
			error:    function (jqXHR, status, error) {
				var parsedResponse = jqXHR.statusText || status || error;
				alert(parsedResponse);
			}
		});
	};

	self.serviceAuthenticationDialog = function (customFields, serviceId) {
		self.serviceDialog.dialog({
			dialogClass: 'wpml-dialog otgs-ui-dialog',
			width:       'auto',
			title:       self.activeService.find( '.active-service-title' ).val(),
			modal:       true,
			open:        function () {

				var customFieldsList;
				var customFieldsForm;
				var customFieldsWrapper = self.serviceDialog.find('.custom_fields_wrapper');
				var firstInput = false;

				customFieldsWrapper.empty();

				customFieldsForm = jQuery('<div></div>');
				customFieldsForm.appendTo(customFieldsWrapper);

				customFieldsList = jQuery('<ul></ul>');
				customFieldsList.appendTo(customFieldsForm);

				jQuery.each(customFields, function (i, item) {
					var itemLabel, itemInput;
					var itemId;
					var customFieldsListItem = jQuery('<li class="wpml-form-row"></li>');
					customFieldsListItem.appendTo(customFieldsList);

					itemId = 'custom_field_' + item.name;
					if ('hidden' !== item.type) {
						itemLabel = jQuery('<label for="' + itemId + '">' + item.label + ':</label>');
						itemLabel.appendTo(customFieldsListItem);
						itemLabel.append('&nbsp;');
					}
					switch (item.type) {
						case 'text':
							itemInput = jQuery('<input type="text" id="' + itemId + '" class="custom_fields" name="' + item.name + '" />');
							break;
						case 'checkbox':
							itemInput = jQuery('<input type="checkbox" id="' + itemId + '" class="custom_fields" name="' + item.name + '" />');
							break;
						default:
							itemInput = jQuery('<input type="hidden" id="' + itemId + '" class="custom_fields" name="' + item.name + '" />');
							break;
					}
					itemInput.appendTo(customFieldsListItem);
					if (!firstInput) {
						itemInput.focus();
					}
				});

				jQuery(':input', this).keyup(function (event) {
					if (self.enterKey === event.keyCode) {
						jQuery(this).closest('.ui-dialog').find('.ui-dialog-buttonpane').find('button.js-submit:first').click();
					}
				});

			},
			buttons:     [
				{
					text:    "Cancel",
					click:   function () {
						jQuery(this).dialog("close");
					},
					'class': 'button-secondary alignleft'
				}, {
					text:    "Submit",
					click:   function () {
						var customFieldsDataStringify;
						var customFieldsData;
						var customFieldsInput;
						self.hideButtons();

						customFieldsInput = jQuery('.custom_fields');
						customFieldsData = {};
						jQuery.each(customFieldsInput, function (i, item) {
							customFieldsData[jQuery(item).attr('name')] = jQuery(item).val();
						});
						customFieldsDataStringify = JSON.stringify(customFieldsData, null, ' ');
						self.customFieldsSerialized.val(customFieldsDataStringify);
						self.translationServiceAuthentication(serviceId, false, 0, null, self.showButtons);
					},
					'class': 'button-primary js-submit'
				}
			]
		});
	};

	self.hideButtons = function () {
		self.ajaxSpinner.appendTo(self.serviceDialog);
		self.serviceDialog.parent().find('.ui-dialog-buttonpane').fadeOut();
	};

	self.showButtons = function () {
		self.serviceDialog.find(self.ajaxSpinner).remove();
		self.serviceDialog.parent().find('.ui-dialog-buttonpane').fadeIn();
	};

	self.translationServiceAuthentication = function (serviceId, button, invalidateService) {
		var invalidate;
		var nonce = jQuery( '.translation_service_authentication' ).val();

		invalidate = invalidateService;
		if ('undefined' === typeof invalidateService) {
			invalidate = 0;
		}

		if (isNaN(serviceId)) {
			alert('service_id isNAN');
		} else if (isNaN(invalidate)) {
			alert('invalidate isNAN');
		}

		if (button) {
			button.attr('disabled', 'disabled');
			button.after(self.ajaxSpinner);
		}

		jQuery.ajax({
			type:     "POST",
			url:      ajaxurl,
			data:     {
				'action':        invalidate ? 'translation_service_invalidation' : 'translation_service_authentication',
				'nonce':         nonce,
				'service_id':    serviceId,
				'invalidate':    invalidate,
				'custom_fields': self.customFieldsSerialized.val()
			},
			dataType: 'json',
			success: function (response) {
				var response_message = jQuery( '.tp_response_message' );
				response = response.data;
				if ( 0 === response.errors ) {
					if (response.reload) {
						location.reload(true);
					} else {
						if (button) {
							button.removeAttr('disabled');
							button.next().fadeOut();
						}
					}
				}

				response_message.html( response.message );
				response_message.show();

				setInterval(function () {
					response_message.fadeOut();
				}, 5000);
			},
			error: function (jqXHR, status, error) {
				var parsedResponse = jqXHR.statusText || status || error;
				alert(parsedResponse);
			},
			complete: function() {
				self.showButtons();
			}
		});
	};

	self.flushWebsiteDetailsCache = function (anchor) {
		var nonce = anchor.data('nonce');

		self.ajaxSpinner.appendTo(anchor);
		self.ajaxSpinner.addClass('is-active');

		if (nonce) {
			jQuery.ajax({
										type:     "POST",
										url:      ajaxurl,
										data:     {
											'action': 'wpml-flush-website-details-cache',
											'nonce':  nonce
										},
										dataType: 'json',
										success:  function (response) {
											self.ajaxSpinner.removeClass('is-active');
											if (response.success) {
												/** @namespace response.redirectTo */
												location.reload(response.data.redirectTo);
											}
										}
									});
		}
	};
};

jQuery(document).ready(function () {
	"use strict";

	var wpmlTranslationServicesDialog = new WPMLTranslationServicesDialog();
	var current_url = location.href;
	var search_section = jQuery( '.ts-admin-section-search' );

	wpmlTranslationServicesDialog.init();

	search_section.find('.search' ).click(function(){
		var param = {
			s: search_section.find('.search-string' ).val()
		};

		window.location.href = current_url + '&' + jQuery.param( param );
	});

	jQuery( '.tablenav .items_per_page select' ).change(function(){
		var param = {
			items_per_page: jQuery( this ).val()
		};
		var items_per_page_url = current_url.replace( /&paged=[^&]*/, '' );

		window.location.href = items_per_page_url + '&' + jQuery.param( param );
	});

	search_section.find( '.search-string' ).keypress(function (e) {
		if ( e.which === 13 ) {
			search_section.find( '.search' ).click();
			return false;
		}
	});

	jQuery( '.ts-admin-section-inactive-services #current-page-selector-top' ).keypress(function (e) {
		if ( e.which === 13 ) {
			var param = {
				paged: jQuery( this ).val()
			};

			window.location.href = current_url + '&' + jQuery.param( param );
		}
	});
});

