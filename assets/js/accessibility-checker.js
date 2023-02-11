(function ($) {
    "use strict";

    $(function () {

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
    edac_frontend_highlight_ajax(edac_id);


    function edac_frontend_highlight_ajax(edac_id) {
        $.ajax({
            url: edac_script_vars.ajaxurl,
            method: 'GET',
            data: { action: 'edac_frontend_highlight_ajax', id: edac_id, nonce: edac_script_vars.nonce }
        }).done(function( response ) {
            if( true === response.success ) {
                
                // Get the response.
                let response_json = $.parseJSON( response.data );
                
                // Parse the response.
                var html = $.parseHTML( response_json.object );

                // 
                var nodeName = html[0].nodeName;
                
                console.log(response_json);
                console.log(html);
                console.log(nodeName);

                //var id = html[0]['id'];
                //var classes = html[0]['classList'];
                /*
                classes.each(function( i ) {
                    console.log(this);
                });
                */
                //console.log('CLASSES: ' + classes);

                var innerText = html[0]['innerText'];

                //console.log(html[0]['attributes']);

                var element_selector = nodeName;
                var atributes_allowed = [
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
                
                
                //if(id && nodeName != 'IMG'){
                    //element_selector += '#'+id;
                //}
                //if(classes && nodeName != 'IMG'){
                    // replace multiple spaces, tabs, new lines etc.
                    // cound use for only spaces /  +/g
                    //classes = classes.replace(/\s\s+/g, '.');
                    //element_selector += '.'+classes.replace(" ",".");
                //}
                if(innerText && nodeName == 'A'){
                    element_selector += ":contains('"+innerText+"')";
                }
                

                var attribute_selector = '';
                $(html[0]['attributes']).each(function( i ) {
                    //console.log(this.nodeName);
                    //console.log(this.nodeValue);
                    if(jQuery.inArray(this.nodeName, atributes_allowed) !== -1 && this.nodeValue != ''){
                        attribute_selector += '['+this.nodeName+'="'+this.nodeValue+'"]';
                    }
                });
                //console.log('attribute_selector:'+attribute_selector);

                element_selector += attribute_selector;


                console.log('Selector:'+element_selector);

                var element = $(element_selector);
                if(element.length){
                    var element_border_color = 'red';
                    if(response_json.ruletype == 'error'){
                        element_border_color = 'red';
                    }else if(response_json.ruletype == 'warning'){
                        element_border_color = 'orange';
                    }
                    //element.css('outline','5px solid '+element_border_color).css('outline-offset','2px');
                    

                    element.wrap('<div class="edac-highlight edac-highlight-'+response_json.ruletype+'"></div>');


                    element.before('<div class="edac-highlight-tooltip-wrap"><button class="edac-highlight-btn edac-highlight-btn-'+response_json.ruletype+'" aria-label="'+response_json.rule_title+'" aria-expanded="false" aria-controls="edac-highlight-tooltip-'+response_json.id+'"></button><div class="edac-highlight-tooltip" id="edac-highlight-tooltip-'+response_json.id+'"><strong class="edac-highlight-tooltip-title">'+response_json.rule_title+'</strong><a href="'+response_json.link+'" class="edac-highlight-tooltip-reference" target="_blank" aria-label="Read documentation for '+response_json.rule_title+', opens new window"><span class="dashicons dashicons-info"></span></a><br /><span>'+response_json.summary+'</span></div></div>');

                    $([document.documentElement, document.body]).animate({
                        scrollTop: $(element).offset().top-50
                    }, 0);

                    // tooltip: hide
                    $('.edac-highlight-tooltip').hide();

                    // tooltip: position
                    function edac_tooltip_position(tooltip){
                        tooltip.next(".edac-highlight-tooltip").fadeIn();
                        var position = tooltip.position();
                        var x = position.left + tooltip.width() + 10;
                        var y = position.top;
                        tooltip.next(".edac-highlight-tooltip").css( { left: x + "px", top: y + "px" } );
                    }

                    // tooltip: hide
                    var timeout;
                    function edac_tooltip_hide() {
                        timeout = setTimeout(function () {
                            $('.edac-highlight-tooltip').fadeOut(400);
                        }, 400);
                    };

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


                        // var css = $('head').find('style[type="text/css"]').add('link[rel="stylesheet"]');
                        // $('head').data('css', css);
                        // css.remove();


                        // $('a#turn_off').click(function(evt) {
                        //     evt.preventDefault();
                        //     var css = $('head').find('style[type="text/css"]').add('link[rel="stylesheet"]');
                        //     $('head').data('css', css);
                        //     css.remove();
                        // });
                    
                        // $('a#turn_on').click(function(evt) {
                        //     evt.preventDefault();
                        //     var css = $('head').data('css');
                        //     console.info(css);
                        //     if (css) {
                        //         $('head').append(css); 
                        //     }
                        // });



                    
                    
                }else{
                    alert('Accessibility Checker could not find the element on the page.');
                }                
            
            } else {
                console.log(response);
            }
        });
    }


    });
})(jQuery);
