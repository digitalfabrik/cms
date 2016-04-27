$(document).ready(function() {

    addResponsiveClassToTables();

    positioningTopHeader();

    toggleLanguageList();

    breadcrumbWrapEachWord();

    toggleBreadcrumb();

    $(window).scroll(function() {

        positioningTopHeader();

    });

});

function positioningTopHeader() {
    if(window.matchMedia("(max-width: 767px)").matches && $('body').hasClass('logged-in')) {
        setTimeout(function() {
            var scrollTop = $(window).scrollTop();

            if(scrollTop > 46) {
                scrollTop = 46;
            }

            $('header#topHeader').css('top',46-scrollTop);
        }, 1);
    }
}

function toggleLanguageList() {
    $('#languageSwitcher').click(function() {
        $('#languageList').addClass('addFlex');
        $('#languageList').animate({ 'opacity':1 },300);
    });
    $('#languageListClose').click(function() {
        $('#languageList').animate({
            'opacity':0
        }, 300, function() {
            $('#languageList').removeClass('addFlex')
        });
    });
}

function breadcrumbWrapEachWord() {
    $('nav#breadcrumb > ul > li .splitText').each(function() {
        var text = $(this).text();
        if( text != '' ) {
            var word = text.split(' ');
            var str = "";
            $.each(word, function (key, value) {
                if (key != 0) {
                    str += " ";
                }
                str += "<span>" + value + "</span>";
            });
            $(this).html(str);
        }
    });
}

function toggleBreadcrumb() {
    setTimeout(function() {
        var outerHeight = $('#breadcrumb').outerHeight();
        var ulOuterHeight = $('#breadcrumb ul').outerHeight();
        if (ulOuterHeight > outerHeight) {
            $('#toggleBreadcrumb').click(function () {
                if ($('#breadcrumb').hasClass('closed')) {
                    $('#breadcrumb').removeClass('closed');
                    $('#breadcrumb #toggleBreadcrumb i').removeClass('fa-plus');
                    $('#breadcrumb #toggleBreadcrumb i').addClass('fa-minus');
                    $('#breadcrumb').animate({height: ulOuterHeight}, 300);
                } else {
                    $('#breadcrumb').addClass('closed');
                    $('#breadcrumb #toggleBreadcrumb i').removeClass('fa-minus');
                    $('#breadcrumb #toggleBreadcrumb i').addClass('fa-plus');
                    $('#breadcrumb').animate({height: outerHeight}, 300);
                }
            });
        } else {
            $('#breadcrumb #toggleBreadcrumb').hide();
        }
    },1);
}

function addResponsiveClassToTables() {
    if(window.matchMedia("(max-width: 767px)").matches) {
        $('.searchOnSiteContent table').addClass('responsive');
    }
}