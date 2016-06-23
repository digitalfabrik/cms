var WpmlTmEditorJobFieldView = Backbone.View.extend({
    events: {
        'click .icl_tm_copy_link': 'copyField'
    },
    copyField: function () {
        var self = this;
        var button = self.$el.find('.icl_tm_copy_link');
        var type = button.attr('id').replace(/^icl_tm_copy_link_/, '');
        var copy_link_element = button.parent();
        tmEditor.icl_get_job_original_contents(self.job_id, type, copy_link_element);

        return false;
    }
});