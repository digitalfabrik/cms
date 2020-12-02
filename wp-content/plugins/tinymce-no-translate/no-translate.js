(function() {
    tinymce.PluginManager.add( 'no_translate_attribute', function( editor, url ) {
        editor.addButton('notranslatebtn', {
            title: 'Wort für Übersetzungen sperren',
            image: url + '/icon.png',
            onclick: function() {
                editor.focus();
                let current_node = tinymce.activeEditor.selection.getStart();
                let end_node = tinymce.activeEditor.selection.getEnd();
                let current_no_translate = editor.dom.getAttrib(current_node, "translate", null);
                if (current_node != end_node) {
                    while (current_node) {
                        if (current_no_translate == "no") {
                            editor.dom.setAttrib(current_node, "translate", null);
                        } else {
                            editor.dom.setAttrib(current_node, "translate", "no");
                        }
                        if (current_node === end_node) {
                            break;
                        }
                        current_node = current_node.nextSibling;
                    }
                } else {
                    if(current_no_translate == "no") {
                        editor.dom.setAttrib(current_node, "translate", null);
                    } else if (editor.selection.getContent().length > 0) {
                        editor.execCommand('mceInsertContent', false, '<span translate="no">' + editor.selection.getContent({'format': 'html'}) + '</span>');
                    }
                }
            }
        });
    });
})();
