/**
 * This file is part of the TinyMCE Advanced WordPress plugin and is released under the same license.
 * For more information please see tinymce-advanced.php.
 *
 * Copyright (c) 2007-2016 Andrew Ozz. All rights reserved.
 */
( function( tinymce ) {
	tinymce.PluginManager.add( 'wptadv', function( editor ) {
		var noAutop = ( ! editor.settings.wpautop && editor.settings.tadv_noautop );

		function addLineBreaks( html ) {
			var blocklist = 'table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre' +
				'|form|map|area|blockquote|address|math|style|p|h[1-6]|hr|fieldset|legend|section' +
				'|article|aside|hgroup|header|footer|nav|figure|figcaption|details|menu|summary';

			html = html.replace( new RegExp( '<(?:' + blocklist + ')(?: [^>]*)?>', 'gi' ), '\n$&' );
			html = html.replace( new RegExp( '</(?:' + blocklist + ')>', 'gi' ), '$&\n' );
			html = html.replace( /(<br(?: [^>]*)?>)[\r\n\t]*/gi, '$1\n' );
			html = html.replace( />\n[\r\n\t]+</g, '>\n<' );
			html = html.replace( /^<li/gm, '\t<li' );
			html = html.replace( /<td>\u00a0<\/td>/g, '<td>&nbsp;</td>' );

			return tinymce.trim( html );
		}

		editor.addCommand( 'Tadv_Mark', function() {
			editor.formatter.toggle('mark');
		});

		editor.addButton( 'tadv_mark', {
			icon: 'backcolor',
			tooltip: 'Mark',
			cmd: 'Tadv_Mark',
			stateSelector: 'mark'
		});

		editor.on( 'init', function() {
			if ( noAutop ) {
				editor.on( 'SaveContent', function( event ) {
					event.content = event.content.replace( /caption\](\s|<br[^>]*>|<p>&nbsp;<\/p>)*\[caption/g, 'caption] [caption' );

					event.content = event.content.replace( /<(object|audio|video)[\s\S]+?<\/\1>/g, function( match ) {
						return match.replace( /[\r\n\t ]+/g, ' ' );
					});

					event.content = event.content.replace( /<pre( [^>]*)?>[\s\S]+?<\/pre>/g, function( match ) {
						match = match.replace( /<br ?\/?>(\r\n|\n)?/g, '\n' );
						return match.replace( /<\/?p( [^>]*)?>(\r\n|\n)?/g, '\n' );
					});

					event.content = addLineBreaks( event.content );
				});
			}

			try {
				if ( editor.plugins.searchreplace && ! editor.controlManager.buttons.searchreplace ) {
					editor.shortcuts.remove( 'meta+f' );
				}
			} catch ( er ) {}

			editor.formatter.register({
				mark: { inline: 'mark' }
			});
		});

		if ( noAutop ) {
			editor.on( 'beforeSetContent', function( event ) {
				var autop = typeof window.wp !== 'undefined' && window.wp.editor && window.wp.editor.autop;

				if ( event.load && autop && event.content && event.content.indexOf( '\n' ) > -1 && ! /<p>/i.test( event.content ) ) {
					event.content = autop( event.content );
				}
			}, true );
		}

		return {
			addLineBreaks: addLineBreaks
		};
	});
}( window.tinymce ));
