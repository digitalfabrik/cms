    var wpml_language_selector_click = {
        ls_click_flag: false,
        toggle: function (icl_switcher) {
            var sel;
            if (icl_switcher != undefined) {
                sel = icl_switcher.children[0].children[0].children[1];
            } else {
                sel = document.getElementById('lang_sel_click').children[0].children[0].children[1];
            }
            
            if(sel.style.visibility == 'visible'){
                sel.style.visibility = 'hidden';
                document.removeEventListener('click', wpml_language_selector_click.close);                
            }else{                                
                sel.style.visibility = 'visible';
                document.addEventListener('click', wpml_language_selector_click.close);                
                wpml_language_selector_click.ls_click_flag = true;
            }
            return false;
        },
        close: function(e){
            if(!wpml_language_selector_click.ls_click_flag){
                var sel = document.getElementById('lang_sel_click').children[0].children[0].children[1];            
                sel.style.visibility = 'hidden';
            }
            wpml_language_selector_click.ls_click_flag = false;
        }
    };