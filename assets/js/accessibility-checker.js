(function ($) {
    "use strict";

    $(function () {

        // tooltip: hide
        let timeout;

        function edac_disabled_styles() { 
            var css = $('head').find('style[type="text/css"]').add('style').add('link[rel="stylesheet"]');

            // remove inline styles
            $('* [style]').not('.edac-highlight-tooltip').removeAttr("style");

            $(css).each(function() {
                //edac-css
                console.log(this.id);
                if( this.id == 'edac-css' || this.id == 'dashicons-css' ) {
                    css.splice( $.inArray(this, css), 1 );
                }
            });

            $('head').data('css', css);
            css.remove();
            //alert("Styles have been disabled. To enable styles please refresh the page.");
        }

        /*
        function edac_enable_styles() { 
            var css = $('head').data('css');
            if (css) {
                $('head').append(css);
            }
        }
        */

        var getUrlParameter = function getUrlParameter(sParam) {
            var sPageURL = window.location.search.substring(1),
                sURLVariables = sPageURL.split('&'),
                sParameterName,
                i;
        
            for (i = 0; i < sURLVariables.length; i++) {
                sParameterName = sURLVariables[i].split('=');
        
                if (sParameterName[0] === sParam) {
                    return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                }
            }
            return false;
        };

        var edac_id = getUrlParameter('edac');
        if(edac_script_vars.loggedIn && edac_id){
            edac_frontend_highlight_ajax(edac_id);
        }

        function edac_frontend_highlight_ajax(edac_id) {
            $.ajax({
                url: edac_script_vars.ajaxurl,
                method: 'GET',
                data: { action: 'edac_frontend_highlight_ajax', id: edac_id, nonce: edac_script_vars.nonce }
            }).done(function( response ) {
                if( true === response.success ) {
                    
                    let response_json = $.parseJSON( response.data );
                    let html = $.parseHTML( response_json.object );
                    let nodeName = html[0].nodeName;
                    console.log( html );
                    let element_selector = nodeName;
                    let innerText = html[0]['innerText'];
                    let inner_text_empty = innerText.replace(/ /g,'');
                    let attribute_selector = '';
                    let atributes_allowed = [
                        'id',
                        'class',
                        'href',
                        'src',
                        'alt',
                        'aria-hidden',
                        'role',
                        'focusable',
                        'width',
                        'height',
                        'aria-label',
                        'rel',
                        'target'
                    ];
                    
                    // If an anchor link and has inner text.
                    if( inner_text_empty && innerText && nodeName == 'A' ){
                        element_selector += ":contains('"+innerText+"')";
                    }
                    
                    // Build attribute selector.
                    $(html[0]['attributes']).each(function() {
                        if(jQuery.inArray(this.nodeName, atributes_allowed) !== -1 && this.nodeValue != ''){
                            attribute_selector += '['+this.nodeName+'="'+this.nodeValue+'"]';
                        }
                    });

                    // Combine element and attribute selectors.
                    element_selector += attribute_selector;
                    console.log( 'Element selector: ' + element_selector );

                    // Get the element.
                    let element = $(element_selector);
                    if(element.length){

                        // Wrap element.
                        element.wrap('<div class="edac-highlight edac-highlight-'+response_json.ruletype+'"></div>');

                        // Add tooltip markup.
                        element.before('<div class="edac-highlight-tooltip-wrap"><button class="edac-highlight-btn edac-highlight-btn-'+response_json.ruletype+'" aria-label="'+response_json.rule_title+'" aria-expanded="false" aria-controls="edac-highlight-tooltip-'+response_json.id+'"></button><div class="edac-highlight-tooltip" id="edac-highlight-tooltip-'+response_json.id+'"><strong class="edac-highlight-tooltip-title">'+response_json.rule_title+'</strong><a href="'+response_json.link+'" class="edac-highlight-tooltip-reference" target="_blank" aria-label="Read documentation for '+response_json.rule_title+', opens new window"><span class="dashicons dashicons-info"></span></a><br /><span>'+response_json.summary+'</span></div></div>');

                        // tooltip: scroll to
                        edac_scroll_to( element );

                        // tooltip: hide
                        $('.edac-highlight-tooltip').hide();

                        // tooltip: btn hover
                        $(".edac-highlight-btn").mouseover(function () {
                            edac_tooltip_position($(this));
                            clearTimeout(timeout);
                            $(this).next('.edac-highlight-tooltip').fadeIn(400);
                        }).mouseout(edac_tooltip_hide);

                        // tooltip: hover
                        $('.edac-highlight-tooltip').mouseover(function () {
                            clearTimeout(timeout);
                        }).mouseout(edac_tooltip_hide);

                        // tooltip: btn focus
                        $(".edac-highlight-btn").click(function () {
                            edac_tooltip_position($(this));
                            if($(this).attr('aria-expanded') == 'false') {
                                $(this).next('.edac-highlight-tooltip').fadeIn(400);
                                $(this).attr('aria-expanded', 'true');
                            }else{
                                $(this).next('.edac-highlight-tooltip').fadeOut(400);
                                $(this).attr('aria-expanded', 'false');
                            }
                        });

                        // set focus on element
                        $('.edac-highlight-btn',element.parent()).first().focus();

                        if($('.edac-highlight-btn',element.parent()).is(':visible')){
                            console.log( 'Element visible: true' );
                        } else {
                            console.log( 'Element visible: false' );
                            if (confirm("The element may be hidden on the page. Would you like to disable styles?")) {
                                edac_disabled_styles();
                            }
                        }

                    } else {
                        alert('Accessibility Checker could not find the element on the page.');
                    }                
                
                } else {
                    console.log(response);
                }
            });
        }

        function edac_scroll_to( element ) {

            let element_offset = element.offset().top;
            //let element_offset_left = element.offset().left;
            let element_height = element.height();
            //let element_width = element.width();
            let window_height = $(window).height();
            
            let offset;

            if (element_height < window_height) {
                offset = element_offset - ((window_height / 2) - (element_height / 2));
            } else {
                offset = element_offset;
            }

            $([document.documentElement, document.body]).animate({scrollTop:offset}, 500);
        }

        function edac_tooltip_position(tooltip){

            let window_width = $(window).width();
                            
            let tooltip_offset_x = 15;
            let tooltip_offset_y = 7;
            let position = tooltip.position();
            let y = position.top + tooltip_offset_y;
            let x = position.left + tooltip.width() + 10;

            if(  position.left > window_width / 2 ) {
                x = (position.left - tooltip.next(".edac-highlight-tooltip").outerWidth()) - tooltip_offset_x;
                tooltip.next(".edac-highlight-tooltip").addClass('edac-highlight-tooltip-left');
            } else {
                x = position.left + tooltip.outerWidth() + tooltip_offset_x;
                tooltip.next(".edac-highlight-tooltip").removeClass('edac-highlight-tooltip-left');
            }

            tooltip.next(".edac-highlight-tooltip").css( { left: x + "px", top: y + "px" } );
            tooltip.next(".edac-highlight-tooltip").fadeIn();
        }

        function edac_tooltip_hide() {
            timeout = setTimeout(function () {
                $('.edac-highlight-tooltip').fadeOut(400);
            }, 400);
        }

    });
})(jQuery);
