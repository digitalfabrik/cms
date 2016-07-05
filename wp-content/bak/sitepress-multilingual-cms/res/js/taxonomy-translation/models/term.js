(function () {
    TaxonomyTranslation.models.Term = Backbone.Model.extend({

        idAttribute: "term_taxonomy_id",

        defaults: function () {
            return {
                name: false,
                trid: false,
                term_taxonomy_id: false,
                language_code: false,
                slug: false,
                parent: false,
                correctedParent: false,
                description: false,
                level: 0,
                correctedLevel: 0,
                source_language_code: false
            };
        },

        save: function (name, slug, description) {

            var self = this;
            slug = slug ? slug : "";
            description = description ? description : "";

            if (name) {
                jQuery.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: 'wpml_save_term',
                        name: name,
                        slug: slug,
                        _icl_nonce: labels.wpml_save_term_nonce,
                        description: description,
                        trid: self.get("trid"),
                        term_language_code: self.get("language_code"),
                        taxonomy: TaxonomyTranslation.classes.taxonomy.get("taxonomy"),
                        force_hierarchical_sync: true
                    },
                    success: function (response) {
                        var newTermData = response.data;

                        if (newTermData.language_code && newTermData.trid && newTermData.slug && newTermData.term_taxonomy_id) {
                            self.set(newTermData);
                            self.trigger("translationSaved");
                            WPML_Translate_taxonomy.callbacks.fire('wpml_tt_save_term_translation', TaxonomyTranslation.classes.taxonomy.get("taxonomy"));
                        } else {
                            self.trigger("saveFailed");
                        }
                        return self;
                    },
                    error: function(){
                        self.trigger("saveFailed");
                        return self;
                    }
                });
            }
        }
    })
})(TaxonomyTranslation);
