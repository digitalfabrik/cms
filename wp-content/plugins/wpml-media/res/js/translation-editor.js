/*jshint browser:true, devel:true */
/*global jQuery, ajaxurl, icl_ajx_url, icl_ajxloaderimg, wpActiveEditor, tinyMCE, tinymce, icl_thickbox_reopen, icl_tb_init, icl_tb_set_size, iclSaveForm, tmEditorStrings */


"use strict";
var tmEditor = {
    dontShowAgain: false,
    copyLinks: {},
    update_copy_link_visibility: function () {
        var self = tmEditor;
        self.copyLinks.each(function () {
            var type = this.id.replace(/^icl_tm_copy_link_/, '');
            var content = self.get_content_for_copy_field(type);
            if (content && content.trim() !== "") {
                jQuery(this).closest('span').hide();
            } else {
                jQuery(this).closest('span').show();
            }
        });
        if (self.copyLinks.filter(':visible').length === 1) {
            self.copyLinks.closest('span').hide();
        } else {
            self.copyLinks.find('#icl_tm_copy_link_icl_all_fields').closest('span').show();
        }
    },
    findInputFieldForCheckBox: function (cbName) {
        var fieldName = cbName.replace(/finished/, 'data');
        var inputField = jQuery('[name="' + fieldName + '"]');
        if (inputField.length === 0) {
            inputField = false;
        }

        return inputField;
    },
    find_custom_editor: function (field_type) {
        var custom_editor = false;
        var editors;
        if (typeof tinyMCE !== 'undefined' && (editors = tinyMCE.editors) !== undefined) {
            jQuery.each(
                editors, function () {
                    var item = this;
                    if (("field-wpcf-" + field_type).toLowerCase() === item.id.toLowerCase()
                        || field_type.toLowerCase() === item.id.toLowerCase()) {
                        custom_editor = item;
                    }
                }
            );
        }
        return custom_editor;
    },
    get_content_for_copy_field: function (field_type) {
        var editor = this.find_custom_editor(field_type);

        return editor !== false ? editor.getContent() : jQuery('#' + field_type).val();
    },
    icl_tm_update_complete_cb_status: function () {
        var icl_tm_editor = jQuery('#icl_tm_editor');
        if (icl_tm_editor.find('.icl_tm_finished:checked').length === icl_tm_editor.find('.icl_tm_finished').length) {
            icl_tm_editor.find(':checkbox[name=complete]').prop('disabled', false);
        } else {
            icl_tm_editor.find(':checkbox[name=complete]').prop('disabled', true);
        }
    },
    icl_populate_field: function (field_type, content) {
        var custom_editor = this.find_custom_editor(field_type);

        if (custom_editor !== false && !custom_editor.isHidden() && custom_editor.getContent().trim() === "") {
            custom_editor.insertContent(content);
        } else {
            custom_editor = jQuery('#' + field_type);
            if('undefined' !== typeof custom_editor && 'undefined' !== typeof custom_editor.val()) {
                custom_editor.val(custom_editor.val().trim() !== "" ? custom_editor.val() : content);
            }
        }
    },
    icl_get_job_original_contents: function (job_id, field_type, calling_element) {
        var self = this;
        var ajax_spinner = jQuery('<span class="spinner" style="float:left;"> </span>');
        calling_element.after(ajax_spinner);
        ajax_spinner.show();
        ajax_spinner.css('visibility', 'visible');
        self.copyLinks.closest('span').hide();
        var nonce = jQuery('#icl_get_job_original_field_content_nonce').val();
        jQuery.ajax(
            {
                type: "POST",
                url: ajaxurl,
                dataType: 'json',
                data: {
                    tm_editor_job_id: job_id,
                    tm_editor_job_field: field_type,
                    action: 'icl_get_job_original_field_content',
                    _icl_nonce: nonce
                },
                success: function (response) {
                    if (response.success) {
                        var fields = response.data;
                        fields.forEach(function (field) {
                            if(field_type === 'icl_all_fields' || field_type === field.field_type) {
                                self.icl_populate_field(field.field_type, field.field_data);
                            }
                        });
                    }
                    ajax_spinner.fadeOut();
                    self.update_copy_link_visibility();
                },
                error: function () {
                    self.update_copy_link_visibility();
                }
            }
        );
    }
};

jQuery(document).ready(function () {
	var wpml_diff_toggle = jQuery('.wpml_diff_toggle');
	wpml_diff_toggle.closest('.wpml_diff_wrapper').find('.wpml_diff').hide();
	wpml_diff_toggle.on('click', function(e) {
		e.preventDefault();

		jQuery(this).closest('.wpml_diff_wrapper').find('.wpml_diff').slideToggle();

		return false;
	});

    jQuery('.tm-learn-more').on('click', function (e) {
        e.preventDefault();

        var popUpHtml = '<div id="tm-editor-learn-more" title="' + tmEditorStrings.title + '">' + tmEditorStrings.learnMore + '</div>';
        jQuery(popUpHtml).dialog();
    });

    var icl_tm_editor = jQuery('#icl_tm_editor');
    icl_tm_editor.find('.handlediv').click(function () {
        if (jQuery(this).parent().hasClass('closed')) {
            jQuery(this).parent().removeClass('closed');
        } else {
            jQuery(this).parent().addClass('closed');
        }
    });

    jQuery('.icl_tm_finished').change(function () {
        jQuery(this).parent().parent().find('.icl_tm_error').hide();

        var field = jQuery(this).attr('name').replace(/finished/, 'data');

        var data;
        var datatemp = '';
        if (field === 'fields[body][data]') {
            try {
                datatemp = tinyMCE.get('body').getContent();
            }
            catch (err) {
            }
            data = jQuery('*[name="' + field + '"]').val() + datatemp;
        } else if (jQuery(this).hasClass('icl_tmf_multiple')) {
            data = 1;
            jQuery('[name*="' + field + '"]').each(function () {
                data = data * jQuery(this).val().length;
            });
        } else {

            try {
                datatemp = tinyMCE.get(field.replace('fields[', '').replace('][data]', '')).getContent();
            }
            catch (err) {
            }

            data = jQuery('[name="' + field + '"]*').val() + datatemp;
            data = jQuery('[name="' + field.toLowerCase() + '"]*').val() + datatemp;
        }

        if (jQuery(this).attr('checked') && !data) {
            jQuery(this).closest('.icl_tm_error').show();
            jQuery(this).removeAttr('checked');
        }
    });

    jQuery('.icl_tmf_term').on('click', function (e) {
        var box = jQuery(this);
        var inputField = tmEditor.findInputFieldForCheckBox(box.attr('name'));
        if (box.attr('checked')) {
            box.attr('checked', 'checked');
        } else {
            if (tmEditor.dontShowAgain === true) {
                inputField.attr('disabled', false);
            } else {
                var popUpHtml = ['<div id="tm-editor-learn-more"><span>',
                    tmEditorStrings.warning,
                    '</span><p><input type="checkbox" class="tm-editor-not-show-again"/>',
                    tmEditorStrings.dontShowAgain,
                    '</p></div>'].join('');
				            jQuery(popUpHtml).dialog(
					            {
						            width  : 'auto',
						            buttons: [
							            {
								            text : "Ok",
								            click: function () {
									            jQuery(this).dialog("close");
									            if (inputField) {
										            inputField.attr('disabled', false);
										            box.attr('checked', false);
										            if (jQuery(this).find('.tm-editor-not-show-again').attr('checked')) {
											            jQuery(this).dialog("destroy");
											            tmEditor.dontShowAgain = true;
										            }
									            }
								            }
							            }, {
								            text : "Cancel",
								            click: function () {
									            jQuery(this).dialog("close");
									            box.attr('checked', 'checked');
								            }
							            }
						            ]
                    });
            }
        }
    });

    tmEditor.copyLinks = jQuery('.icl_tm_copy_link');

    tmEditor.copyLinks.click(function () {
        var type = jQuery(this).attr('id').replace(/^icl_tm_copy_link_/, '');
        var job_id = jQuery('[name="job_id"]').val();
        var copy_link_element = jQuery(this).parent();
        tmEditor.icl_get_job_original_contents(job_id, type, copy_link_element);

        return false;
    });

    tmEditor.copyLinks.each(function () {
        var type = this.id.replace(/^icl_tm_copy_link_/, '');
        if (tmEditor.find_custom_editor(type) === false) {
            jQuery('#' + type).on('keyup', tmEditor.update_copy_link_visibility);
        }
    });

    icl_tm_editor.submit(function () {
        var formnoerr = true;
        var validation_error = jQuery('#icl_tm_validation_error');

        validation_error.hide();

        jQuery('.icl_tm_finished:checked').each(function () {
            var field = jQuery(this).attr('name').replace(/finished/, 'data');

            var data;
            var current_input_field = jQuery('*[name="' + field + '"]');
            current_input_field = current_input_field.length ? current_input_field : jQuery('*[name="' + field.toLowerCase() + '"]');
            if (field === 'fields[body][data]' || current_input_field.hasClass('wp-editor-area')) {
                var editor = typeof tinyMCE !== 'undefined' ? tinyMCE.get(current_input_field.attr('id')) : false;
                editor = editor ? editor : tinyMCE.get(current_input_field.attr('id').toLowerCase());
                if (editor) {
                    data = editor.getContent();
                }
                if (!editor || !data) {
                    data = current_input_field.val();
                }
            } else if (jQuery(this).hasClass('icl_tmf_multiple')) {
                data = 1;
                jQuery('[name*="' + field + '"]').each(function () {
                    data = data * jQuery(this).val().length;
                });
            } else {
                data = jQuery('[name="' + field + '"]*').val();
                data = data ? data : jQuery('[name="' + field.toLowerCase() + '"]*').val();
            }

            if (!data) {
                validation_error.fadeIn();
                jQuery(this).removeAttr('checked');
                tmEditor.icl_tm_update_complete_cb_status();
                formnoerr = false;
            }
        });

        return formnoerr;
    });

    icl_tm_editor.find('.icl_tm_finished').change(tmEditor.icl_tm_update_complete_cb_status);

    tmEditor.update_copy_link_visibility();
});
