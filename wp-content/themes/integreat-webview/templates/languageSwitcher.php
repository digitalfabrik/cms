<div id="languageSwitcher">
    <?php
    $languages = icl_get_languages('skip_missing=1&orderby=id&order=desc');
    $currentLanguage = '';
    $languageList = '';
    $counterLanguages = 0;

    foreach($languages as &$language) {
        if (ICL_LANGUAGE_CODE != $language[code]) {
            if( is_search() ) {
                $languageLink = get_bloginfo('wpurl') . '/' . $language[code] . '/';
            } else {
                $languageLink = $language[url];
            }

            $languageList .= '<a href="';
            $languageList .= $languageLink;
            $languageList .= '?sc=1">';
            $languageList .= '<div class="flag"><img src="';
            $languageList .= $language[country_flag_url];
            $languageList .= '" /></div><div class="nativeName"><span>';
            $languageList .= $language[native_name];
            $languageList .= '</span></div></a>';
            $counterLanguages++;
        } else {
            $currentLanguage .= '<div class="flag"><img src="';
            $currentLanguage .= $language[country_flag_url];
            $currentLanguage .= '" /></div><span>';
            $currentLanguage .= $language[native_name];
            $currentLanguage .= '</span>';
        }
    }
    ?>
    <div id="currentLanguage">
        <?php echo $currentLanguage; ?>
        <div id="numberMoreLanguages">
            +<?php echo $counterLanguages; ?>
        </div>
    </div>
</div>

<div id="languageList">
    <div id="languageListClose"><i class="fa fa-times"></i></div>

    <div class="inner">
        <?php echo $languageList; ?>
    </div>
</div>