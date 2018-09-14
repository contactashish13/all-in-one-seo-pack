(function($, sitemap){
    
    $(document).ready(function(){
        initAll();
    });

    function initAll(){
        initTaxonomyListener();
        initChosen();
    }

    function showLoading(element, type, name){
        element.find('.chosen-container').hide();
        element.find('.aioseop_option_div').append($('<div class="aioseop-chosen-loading aioseop-chosen-loading-' + type + '">' + sitemap.l10n.loading + ' ' + name + '...</div>'));
    }

    function hideLoading(element, type){
        element.find('.aioseop_option_div .aioseop-chosen-loading-' + type).remove();
        if(element.find('.aioseop_option_div .aioseop-chosen-loading').length === 0){
            element.find('.chosen-container').show();
        }
    }

    function initTaxonomyListener(){
        var combo = $('[name="aiosp_sitemap_excl_categories[]"]');
        var combo_wrapper = $('#aiosp_sitemap_excl_categories_wrapper');
        $('.aioseop-excl-taxonomy').on('click', function(e){
            var taxonomy = $(this);
            if(taxonomy.is(':checked')){
                showLoading(combo_wrapper, taxonomy.val(), taxonomy.parent().text().trim());
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
                        hideLoading(combo_wrapper, taxonomy.val());
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