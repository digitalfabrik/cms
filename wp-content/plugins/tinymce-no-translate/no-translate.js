(function() {
    tinymce.PluginManager.add( 'no_translate_attribute', function( editor, url ) {
        editor.addButton('notranslatebtn', {
            title: 'Wort für Übersetzungen sperren',
            image: url + '/icon.png',
            onclick: function() {
                editor.focus();
                val = tinymce.activeEditor.dom.getAttrib(tinyMCE.activeEditor.selection.getNode(), "translate", "yes");
                console.log(editor.selection.getContent().length);
                if(val == "no") {
                    tinymce.activeEditor.dom.setAttrib(tinyMCE.activeEditor.selection.getNode(), "translate", null);
                } else if (editor.selection.getContent().length > 0) {
                    editor.selection.setContent('<span translate="no">' + editor.selection.getContent() + '</span>');
                }
            }
        });
    });
})();
