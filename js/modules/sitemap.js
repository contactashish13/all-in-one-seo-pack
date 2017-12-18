(function($, sitemap){
    
    $(document).ready(function(){
        initAll();
    });

    function initAll(){
        initTaxonomyListener();
        initChosen();
    }

    function showSpinner(element){
        // TODO.
    }

    function hideSpinner(element){
        // TODO.
    }

    function initTaxonomyListener(){
        var combo = $('[name="aiosp_sitemap_excl_categories[]"]');
        var combo_wrapper = $('#aiosp_sitemap_excl_categories_wrapper');
        $('.aioseop-excl-taxonomy').on('click', function(e){
            var taxonomy = $(this);
            if(taxonomy.is(':checked')){
                showSpinner(combo_wrapper);
                $.ajax({
                    url     : ajaxurl,
                    method  : 'post',
                    type    : 'json',
                    data    : {
                        action      : sitemap.ajax.action,
                        _action     : 'fetch_terms',
                        nonce       : sitemap.ajax.nonce,
                        taxonomy    : taxonomy.val()
                    },
                    success : function(data){
                        if(data.data.terms){
                            var $group = '<optgroup label="' + taxonomy.parent().text().trim() + '">';
                            $.each(data.data.terms, function(slug, name){
                                $group += '<option value="' + slug + '">' + name + '</option>';
                            });
                            $group += '</optgroup>';
                            combo.append($group);
                            combo.trigger("chosen:updated");
                        }
                        hideSpinner(combo_wrapper);
                    }
                });
            }else{
                combo.find('optgroup[label="' + taxonomy.parent().text().trim() + '"]').remove();
                combo.trigger("chosen:updated");
            }
        });
    }

    function initChosen(){
        $('.aioseop-chosen').chosen({
            width               : '95%',
            search_contains     : true,
            max_shown_results   : 20,
            rtl                 : true,
            placeholder_text_multiple : sitemap.l10n.choose_terms,
            no_results_text : sitemap.l10n.terms_not_found
        });
    }

})(jQuery, sitemap);