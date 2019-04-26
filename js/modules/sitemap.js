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
            no_results_text : sitemap.l10n.term_searching,
            display_selected_options: false,
        });

        // when the text box becomes unresponsive (nothing can be typed in), lets's do the below
        $.fn.enableTextBox = function(){
            $(this).parent().find("input[type='text']").addClass('default').css('width', '200px');
        }

        $('.aioseop-chosen').on('change, chosen:hiding_dropdown', function(evt, params) {
            $(".aioseop-chosen").enableTextBox();
        });

        // search for a taxonomy term by providing a string (executes a LIKE %string% match with the taxonomy terms)
        $('.chosen-container input').autocomplete({
            delay: 500, // http://api.jqueryui.com/autocomplete/#option-delay
            minLength: 3, // http://api.jqueryui.com/autocomplete/#option-minLength
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
                            $('.aioseop-chosen').prepend('<option value="-1" disabled>' + data.data.msg + '</option>');
                            $(".aioseop-chosen").trigger("chosen:updated");
                            $('.aioseop-chosen option[value="-1"]').remove();
                            setTimeout( function(){
                                $(".aioseop-chosen").trigger("chosen:updated");
                                $(".aioseop-chosen").enableTextBox();
                            }, 1000 );
                            return;
                        }
                        response( $.map( data.data, function( item ) {
                            // do not add duplicates.
                            if($('.aioseop-chosen option[value="' + item.id + '"]').length === 0){
                                $('.aioseop-chosen').prepend('<option value="'+item.id+'">' + item.name + '</option>');
                            }
                        }));
                        $(".aioseop-chosen").trigger("chosen:updated");
                        $(".aioseop-chosen").enableTextBox();
                    }
                });
            }
        });
    }

})(jQuery, sitemap);