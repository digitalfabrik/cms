<div id="fixedSearch" class="searchOnSite">
    <form role="search" method="get" class="search-form" action="<?php echo home_url( '/' ); ?>">
        <input type="search" class="search-field" id="fixedSearchText" placeholder="<?php _e( 'Search on this site', 'integreat' ); ?>" name="s" title="Suchen" data-list=".searchOnSiteContent" data-textsearchininstance="<?php _e( 'Search everywhere', 'integreat' ); ?>" data-textsearchonsite="<?php _e( 'Search on this site', 'integreat' ); ?>" />
        <input type="submit" class="search-submit hidden" value="Suchen" />
        <input type="hidden" name="post_type" value="page,event" />
        <div id="fixedSearchToggle">
            <div>
                <i class="fa fa-search"></i>
            </div>
        </div>
    </form>

    <div id="onSiteSearchOptions" class="searchOptions">
        <span class="prev prevnext"><?php _e( 'Previous', 'integreat' ); ?></span>
        <span class="next prevnext"><?php _e( 'Next', 'integreat' ); ?></span>
        <span class="searchInInstance changeSearchType"><?php _e( 'Search everywhere', 'integreat' ); ?></span>
    </div>

    <div id="searchInInstanceOptions" class="searchOptions">
        <span class="searchOnSite changeSearchType"><?php _e( 'Search on this site', 'integreat' ); ?></span>
    </div>
</div>