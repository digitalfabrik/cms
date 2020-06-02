window._wpLoadBlockEditor.then( function() {
    
    if ( !ure_pro_data.hasOwnProperty('blocked_gb_components') || ure_pro_data.blocked_gb_components.length==0 ) {
        return;    
    }
    
    for( var i=0; i<ure_pro_data.blocked_gb_components.length; i++ ) {
        wp.data.dispatch( 'core/edit-post' ).removeEditorPanel( ure_pro_data.blocked_gb_components[i] );
    }    
    
} );
