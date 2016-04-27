/*globals labels */

(function () {
    TaxonomyTranslation.views.TermPopUpView = Backbone.View.extend({

        tagName: "div",
        template: TaxonomyTranslation.getTemplate("termPopUp"),
        model: TaxonomyTranslation.models.Term,

        events: {
            "click .cancel": "close",
            "click .term-save": "saveTerm",
            "keydown" : "handleEnter"
        },
        initialize: function () {
            var self = this;
            self.listenTo(self.model, 'translationSaved', self.close);
            self.listenTo(self.model, 'saveFailed', self.render);
            var trid = self.model.get("trid");
            self.originalName = TaxonomyTranslation.classes.taxonomy.getOriginalName(self.model.get("language_code"), trid);

            return self;
        },

        open: function () {
            this.$el.show();
            this.delegateEvents();
        },
        close: function () {
            this.$el.hide();
            this.undelegateEvents();
            this.remove();
        },
        get_slug: function(){
            var self = this;

            var slug = self.model.get("slug");
            if (!slug) {
                slug = "";
            }
            slug = decodeURIComponent(slug);

            return slug;
        },
        render: function () {

            var self = this;
            var trid = self.model.get("trid");
            var slug = self.get_slug();

            var description = self.model.get("description");
            if (!description) {
                description = "";
            }
            var name = self.model.get("name");
            if (!name) {
                name = "";
            }

            self.$el.html(
                this.template({
                    trid: trid,
                    lang: self.model.get("language_code"),
                    slug: slug,
                    description: description,
                    langs: TaxonomyTranslation.data.activeLanguages,
                    originalName: self.originalName,
                    ttid: self.model.get("term_taxonomy_id"),
                    name: name
                })
            );

            self.delegateEvents();
            return self;
        },
        handleEnter: function(e){
            var self = this;
            if(self.$el.find('input:focus').length !== 0 && e.keyCode == 13){
                self.saveTerm(e);
            }
            return self;
        },
        saveTerm: function (e) {
            var self = this;

            self.undelegateEvents();

            e.preventDefault();
            var name = self.$el.find("#term-name").val();
            var slug = self.$el.find("#term-slug").val();
            var description = self.$el.find("#term-description").val();

            if (name) {
                self.$el.find(".spinner").show();
                self.$el.find(".term-save").hide();
                self.$el.find(".cancel").hide();
                self.model.save(name, slug, description);
            }

            return self;
        }
    });
})(TaxonomyTranslation);