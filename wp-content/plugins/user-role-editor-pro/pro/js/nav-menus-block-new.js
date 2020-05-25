
jQuery(function() {
    
    // Remove 'add new menu' link
    jQuery('span.add-new-menu-action').find('a').remove();
    jQuery('span.add-edit-menu-action').find('a').remove();
    
    // Remove 'Manage Locations' tab
    var tabs = jQuery('.nav-tab-wrapper').find('a');
    if (tabs.length>1) {
        tabs[1].remove();
    }
});