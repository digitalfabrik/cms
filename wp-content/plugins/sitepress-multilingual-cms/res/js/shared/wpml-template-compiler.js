var WpmlTemplateCompiler = {
    /**
     * @constructor
     *
     * @param {Object} usInstance an instance of the underscore library
     * @param {Object} templates a hash holding array representations of un-compiled
     * underscore templates
     */
    init: function (usInstance, templates) {
        this.us = usInstance;
        this.templates = templates;
    },
    compiledTemplates: {},
    templates: {},
    /**
     *
     * @param {string} temp
     * @returns {*|false} compiled underscore template if a template for the given
     * index was found, false if no such template exists
     */
    getTemplate: function (temp) {
        var self = this;
        if (!self.templates.hasOwnProperty(temp)) {
            throw 'No such template: ' + temp;
        }
        if (self.compiledTemplates[temp] === undefined) {
            self.compiledTemplates[temp] = self.us.template(self.templates[temp].join("\n"))
        }
        return self.compiledTemplates[temp];
    }
};