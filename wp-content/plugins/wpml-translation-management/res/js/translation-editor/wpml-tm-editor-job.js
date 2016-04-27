var WpmlTmEditorJob = Backbone.Model.extend({
    getOriginalContents: function (field_type, callback, doneCallback) {
        var self = this;

        if (self.get('loaded')) {
            self.populateField(field_type, callback, doneCallback);
        } else {
            jQuery.ajax(
                {
                    type: "POST",
                    url: self.url(),
                    dataType: 'json',
                    data: {
                        tm_editor_job_id: self.get('job_id'),
                        action: 'icl_get_job_original_field_content',
                        _icl_nonce: self.get('nonce')
                    },
                    success: function (response) {
                        if (response.success) {
                            var types = [];
                            _.each(response.data, function (field) {
                                /**
                                 * @param {String} field.field_type
                                 * @param {String} field.field_data
                                 */
                                self.set(field.field_type, field.field_data);
                                types.push(field.field_type);
                            });
                            self.set('types', types);
                        }
                        self.set('loaded', true);
                        self.populateField(field_type, callback, doneCallback);
                    }
                }
            );
        }
    },
    /**
     * Overrides the BackBone url method to use the WordPress ajax endpoint
     *
     * @returns {String}
     */
    url: function () {

        return ajaxurl;
    },
    populateField: function (field_type, callback, doneCallback) {
        var self = this;
        if (field_type === 'icl_all_fields') {
            _.each(self.get('types'), function (type) {
                callback(type, self.get(type));
            });
        } else {
            callback(field_type, self.get(field_type));
        }
        doneCallback();
    }
});