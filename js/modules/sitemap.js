/* global sitemap */

(function($, sitemap){
    
    $(document).ready(function(){
        initAll();
    });

    function initAll(){
        initAutocompleteChosen();
    }

    function initAutocompleteChosen(){
        $('.aioseop-chosen').chosen({
            width               : '95%',
            max_shown_results   : 20,
            rtl                 : true,
            placeholder_text_multiple : sitemap.l10n.choose_terms,
            no_results_text : sitemap.l10n.term_searching
        });

        // search for a taxonomy term by providing a string (executes a LIKE %string% match with the taxonomy terms)
        $('.chosen-container input').autocomplete({
            // minLength: 3, // TODO: consider populating this later
            source: function( request, response ) {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    dataType: "json",
                    data: {
                        term: request.term,
                        nonce: sitemap.ajax.nonce,
                        action: sitemap.ajax.action,
                        _action: 'fetch_terms',
                        taxonomy: $('[name="aiosp_sitemap_taxonomies[]"]:checked').serialize()
                    },
                    success: function( data ) {
                        // if there is no match.
                        if( ! data.success ) {
                            // we cannot change the value of "no_results_text" at runtime
                            // so we populate a disabled option with value -1 and remove it in 1 second
                            $('.aioseop-chosen').append('<option value="-1" disabled>' + data.data.msg + '</option>');
                            $(".aioseop-chosen").trigger("chosen:updated");
                            $('.aioseop-chosen option[value="-1"]').remove();
                            setTimeout( function(){$(".aioseop-chosen").trigger("chosen:updated");}, 1000 );
                            return;
                        }
                        response( $.map( data.data, function( item ) {
                            $('.aioseop-chosen').append('<option value="'+item.id+'">' + item.name + '</option>');
                        }));
                        $(".aioseop-chosen").trigger("chosen:updated");
                    }
                });
            }
        });
    }

})(jQuery, sitemap);