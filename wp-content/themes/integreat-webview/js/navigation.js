$(document).ready(function() {

    var menuElem = $('nav#mainNav');

    // change children toggle icon if sub menu is opened
    var liOfChildrenToggle = $('.current_page_parent, .current_page_ancestor').find('> a > .childrenToggle i');
    if(liOfChildrenToggle.hasClass('fa-plus')) {
        liOfChildrenToggle.removeClass('fa-plus');
        liOfChildrenToggle.addClass('fa-minus');
    }

    // open
    $('#openMainNav').click(function(e) {
        e.preventDefault();
        //if (window.matchMedia("(min-width: 991px)").matches) {
            $('body').addClass('has-active-menu');
            $('#siteWrap').addClass('has-push-left');
            menuElem.addClass('is-active');
            $('#mainNavMask').addClass('is-active');
        //}
    });

    // close
    $('#closeMainNav').click(function(e) {
        e.preventDefault();
        //if (window.matchMedia("(min-width: 991px)").matches) {
            $('body').removeClass('has-active-menu');
            $('#siteWrap').removeClass('has-push-left');
            menuElem.removeClass('is-active');
            $('#mainNavMask').removeClass('is-active');
        //}
    });

    /**
     * toggle sub-menu
     */
    $('#mainMenu .childrenToggle').click(function(e) {
        e.preventDefault();
        var correspondingChildrenUl = jQuery(this).parent().parent().find('> ul.children');

        if (correspondingChildrenUl.css('display') == 'block') {
            jQuery(this).find('.borderBottom').css('display', 'none');
        } else {
            jQuery(this).find('.borderBottom').css('display', 'block');
        }

        var childrenToggle = $(this);

        correspondingChildrenUl.slideToggle(300, function() {
            $(childrenToggle).find('i').toggleClass('fa-plus');
            $(childrenToggle).find('i').toggleClass('fa-minus');
            initCustomScrollbar();
        });
    });

    // custom scrollbar
    initCustomScrollbar();

    $(window).resize(function() {
        initCustomScrollbar();
    });

    /*
    $(function () {
        // Collapse / uncollapse menu
        $('#mainNav ul').mouseover(function (e) {
            e.stopPropagation();
            console.log('hover');
            var depth = depthOfList( $('ul#mainMenu') ) - 1;
            $(this).children().children('ul').css({
                'left': (336 - depth * 48) + 'px'});
        }).mouseout(function (e) {
            e.stopPropagation();
            $(this).find('ul').css({
                'left': '48px'});
        });

        // Get depth of list
        function depthOfList(elem){
            var $children = $(elem).children('li.page_item_has_children');
            if($children.length){
                return 1 + depthOfList( $children.find('> ul') );
            }
            return 1;
        }
    });
    */

});

// custom scrollbar
function initCustomScrollbar() {
    setTimeout(function() {
        $('nav#mainNav').customScrollbar();
    }, 10);
}