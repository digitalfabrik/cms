$(document).ready(function() {

    openSearchText();

    changeSearchType();

    searchOnSiteCalls();

    searchOnSitePrevNext();

});

function openSearchText() {
    $('#fixedSearchToggle').click(function() {
        if( $('#fixedSearchText').css('display') == 'none' ) {
            var width = $('#fixedSearch').width() - 110;
            $('#fixedSearch').addClass('addBackground');
            $('#fixedSearchText').css('display','block');
            $('#fixedSearchText').animate({'width':width,'bottom':'45px'},300);
            $('#fixedSearchToggle').animate({'bottom':'37px'},300);
            $('#onSiteSearchOptions').addClass('addFlex');
            $('#onSiteSearchOptions').animate({'opacity':1},300);

            var input = $('#fixedSearchText');
            input[0].selectionStart = input[0].selectionEnd = input.val().length;

            $('body').css('paddingBottom',105);
        } else {
            $('#fixedSearch input[type=submit]').trigger('click');
        }
    });
}

function changeSearchType() {
    $('.changeSearchType').click(function() {
        if($('body').hasClass('searchOnSite')) {
            $('body').removeClass('searchOnSite');
            $('body').addClass('searchInInstance');
            $('#fixedSearchText').attr('placeholder',$('#fixedSearchText').data('textsearchininstance'));
        } else {
            $('body').removeClass('searchInInstance');
            $('body').addClass('searchOnSite');
            $('#fixedSearchText').attr('placeholder',$('#fixedSearchText').data('textsearchonsite'));
        }
    });
}

// call function for initialisation of on site search
function searchOnSiteCalls() {
    searchOnSite();

    $("#fixedSearch form").submit(function(e){
        searchOnSite();
    });
}

// initialise on site search
function searchOnSite() {
    // prevent wordpress search activation on click or enter
    $("#fixedSearch form").submit(function(e){
        if($('body').hasClass('searchOnSite')) {
            return false;
        }
    });

    // search on site
    if($('body').hasClass('searchOnSite')) {
        $('#fixedSearchText').hideseek({
            highlight: true,
            ignore_accents: true,
        });
    }
}

// on site search: find previous and next highlighted literal
function searchOnSitePrevNext() {
    $('#onSiteSearchOptions .prevnext').click(function() {
        $('.searchOnSiteContent mark.highlight').removeClass('current');

        var markPosition = 0;
        if ($("#onSiteSearchOptions").attr('data-mark')) {
            markPosition = $('#onSiteSearchOptions').attr('data-mark');
        }

        markPosition = parseInt(markPosition);

        if($(this).hasClass('prev')) {
            searchOnSiteFindMarkAndScroll(markPosition-1);
        } else if($(this).hasClass('next')) {
            searchOnSiteFindMarkAndScroll(markPosition+1);
        }
    });
}

// find a highlighted literal (mark) at position given in parameter
function searchOnSiteFindMarkAndScroll(pos) {
    var position = parseInt(pos);

    var numberOfMarks = $('.searchOnSiteContent mark.highlight').length - 1;

    // handle overrun
    if(position < 1) {
        position = numberOfMarks + 1 + position;
    }
    if(position > numberOfMarks) {
        position = position - numberOfMarks - 1;
    }

    // find and scroll to mark at position
    $('.searchOnSiteContent mark.highlight').eq(position).addClass('current');

    $('html, body').animate({
        scrollTop: $('.searchOnSiteContent mark.highlight').eq(position).offset().top - 80
    }, 150);

    $('#onSiteSearchOptions').attr('data-mark',position);
}