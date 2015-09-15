/*globals labels */

(function () {

    TaxonomyTranslation.views.TermView = Backbone.View.extend({

        tagName: "td",
        template: TaxonomyTranslation.getTemplate("termTranslated"),
        model: TaxonomyTranslation.models.Term,
        popUpView: false,
        needsCorrection: false,
        events: {
            "click .icl_tt_term_name": "openPopUPTerm"
        },

        initialize: function () {
            var self = this;
            self.listenTo(self.model, 'translationSaved', self.render);
            self.listenTo(self.model, 'translationSaved', function () {
                jQuery('#tax-apply').prop('disabled', false);
            });
        },
        loadSyncData: function () {
            "use strict";
            var self = this;

            var syncData = TaxonomyTranslation.data.syncData;
            var ttid = self.model.get('term_taxonomy_id');
            var parent = self.model.get('parent');
            var found = false;
            var needsCorrection = false;
            var correctParentText = false;
            var parentName = TaxonomyTranslation.classes.taxonomy.getTermName(parent);
            _.each(syncData, function (correction) {
                if (correction.translated_id == ttid) {
                    found = true;

                    var oldParent = '';
                    if (parent != 0) {
                        oldParent = '<span style="background-color:#F55959;">-' + TaxonomyTranslation.classes.taxonomy.getTermName(parent) + '</span>';
                        jQuery('.wpml-parent-removed').show();
                    }
                    var newParent = '';
                    if (correction.correct_parent != 0) {
                        newParent = '<span style="background-color:#CCFF99;">+' + TaxonomyTranslation.classes.taxonomy.getTermName(correction.correct_parent) + '</span>';
                        jQuery('.wpml-parent-added').show();
                    }
                    parentName = oldParent + '   ' + newParent;
                    needsCorrection = true;
                }
            });

            if (needsCorrection === true) {
                self.template = TaxonomyTranslation.getTemplate("termNotSynced");
            } else {
                self.template = TaxonomyTranslation.getTemplate("termSynced");
            }

            self.$el.html(
                self.template({
                    trid: self.model.get("trid"),
                    lang: self.model.get("language_code"),
                    name: self.model.get("name"),
                    level: self.model.get("level"),
                    correctedLevel: self.model.get("level"),
                    correctParent: correctParentText,
                    parent: parentName
                })
            );

            self.needsCorrection = needsCorrection;

            return self;
        },

        render: function () {
            var self = this;

            self.needsCorrection = false;
            if (!self.model.get("name")) {
                self.template = TaxonomyTranslation.getTemplate("termNotTranslated");
            } else {
                self.template = TaxonomyTranslation.getTemplate("termTranslated");
            }

            self.$el.html(
                self.template({
                    trid: self.model.get("trid"),
                    lang: self.model.get("language_code"),
                    name: self.model.get("name"),
                    level: self.model.get("level"),
                    correctedLevel: self.model.get("level")
                })
            );

            self.delegateEvents();
            return self;
        },
        openPopUPTerm: function (e) {
            var self = this;

            e.preventDefault();
            var trid = self.model.get("trid");
            var lang = self.model.get("language_code");
            if (trid && lang) {
                if (TaxonomyTranslation.classes.termPopUpView && typeof TaxonomyTranslation.classes.termPopUpView !== 'undefined') {
                    TaxonomyTranslation.classes.termPopUpView.close();
                }
                TaxonomyTranslation.classes.termPopUpView = new TaxonomyTranslation.views.TermPopUpView({model: self.model});

                var popUpHTML = TaxonomyTranslation.classes.termPopUpView.render().el;
                var popUpDomEl = jQuery("#" + trid + '-popup-' + lang);
                popUpDomEl.html(popUpHTML);
                var iclttForm = popUpDomEl.find('.icl_tt_form');
                iclttForm.show();
                iclttForm.first('input').focus();
                TaxonomyTranslation.classes.termPopUpView.$el.find('.term-save').on("click", TaxonomyTranslation.classes.termPopUpView.saveTerm.bind(TaxonomyTranslation.classes.termPopUpView));
            }
        }
    })
})(TaxonomyTranslation);
