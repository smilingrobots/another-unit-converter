if ( typeof jQuery !== 'undefined' ) {
    (function($) {
        $(function() {
            /**
             * Formats a number with grouped thosands.
             * Taken from http://stackoverflow.com/a/14428340
             */
            var aucp_number_format = function( number, decimals, dec_point, thousands_sep ) {
                var re = '\\d(?=(\\d{' + (3 || 3) + '})+' + (decimals > 0 ? '\\D' : '$') + ')',
                    num = number.toFixed(Math.max(0, ~~decimals));

                return (decimals ? num.replace('.', dec_point) : num).replace(new RegExp(re, 'g'), '$&' + (thousands_sep || ',')); 
            };

            /**
             * Converts everything to a given target currency.
             */
            var aucp_convert_amounts = function( target ) {
                var sources = $('[data-unit-converter-currency-code]');
                var codes = sources.map(function() {
                    return $(this).attr('data-unit-converter-currency-code');
                }).get();

                // Convert.
                $.getJSON(aucp_js.ajaxurl, {
                    action: 'aucp_get_rates',
                    codes: $.unique(codes.concat(['USD', target]))
                }).done(function(data) {
                    sources.each(function() {
                        var $currencyAmount = $(this),
                            amount = $currencyAmount.attr('data-unit-converter-currency-amount'),
                            code = $currencyAmount.attr('data-unit-converter-currency-code'),
                            symbol = $currencyAmount.attr('data-unit-converter-currency-symbol'),
                            fromRate = data.rates[code].rate,
                            toRate = data.rates[target].rate,
                            newAmount = toRate * (parseFloat(amount) / fromRate),
                            // http://www.jacklmoore.com/notes/rounding-in-javascript/
                            roundedAmount = Number(Math.round(newAmount+'e2')+'e-2');

                        if ( code == target ) {
                            $currencyAmount.removeClass( 'aucp-converted' );
                            return;
                        }


                        var template = data.rates[target].format_template;
                        var formattedNumber = aucp_number_format( roundedAmount, data.rates[target].decimal_places, data.rates[target].decimal_point, data.rates[target].thousands_separator );

                        $currencyAmount.find( '.aucp-converted-text' ).html( template.replace( '<amount>', formattedNumber ) ).attr( 'title', data.rates[target].name );
                        $currencyAmount.addClass( 'aucp-converted' );
                    })
                });
            };


            var $widget = $( $('#aucp-currency-switcher-template').html() );


            var ENTER = 13,
                SPACE = 32,
                UP = 38,
                DOWN = 40,
                RIGHT = 39,
                LEFT = 37;

            $widget.on( 'keydown', 'input', function(e) {
                switch ( e.keyCode ) {
                    case UP:
                    case DOWN:
                        // We do this so you can not move inside the textfield usign the up/down arrow keys.
                        e.preventDefault();
                        break;
                    default:
                        break;
                }
            } );
            $widget.on('keyup.aucp', 'input', function(e) {
                var $items = $( '.aucp-currency-switcher-currencies-list-item' );

                switch ( e.keyCode ) {
                    case ENTER:
                        var $focused = $items.filter( '.navigation-focus' ).first();
                        $focused.click();
                        break;
                    case SPACE:
                        break;
                    case UP:
                    case DOWN:
                        var delta = e.keyCode == UP ? -1 : 1;
                        var $focused = $items.filter( '.navigation-focus' );

                        // XXX: Can this happen?
                        if ( 1 != $focused.length ) {
                            return;
                        }

                        var $visible_items = $items.filter( ':visible' );
                        var new_index = Math.max( 0, Math.min( $visible_items.index( $focused ) + delta, $visible_items.length - 1 ) );

                        $items.removeClass( 'navigation-focus' );
                        $visible_items.eq( new_index ).addClass( 'navigation-focus' );
                        $focused = $visible_items.eq( new_index );

                        // Scroll the container to see the element.
                        var $container = $widget.find( '.aucp-currency-switcher-currencies-list' );
                        var container_height = $container.height(),
                                           t = $focused.position().top,
                                           h = $focused.outerHeight();
                        var scroll = false;

                        if ( t > 0 && ( t + h ) > container_height ) {
                            scroll = $container.scrollTop() + h;
                        } else if ( t < 0 ) {
                            scroll = $container.scrollTop() + t;
                        }

                        if ( false !== scroll ) {
                            $container.scrollTop( scroll );
                        }

                        break;
                    default:
                        var search = $(this).val().toLowerCase();

                        $items.show();
                        $items.removeClass( 'navigation-focus' );
                        $items.parent().scrollTop( 0 );

                        if ( search.length ) {
                            $items.not('[data-content*="' + search + '"]').hide();
                        }

                        var $visible_items = $items.filter( ':visible' );
                        if ( $visible_items.length > 0 ) {
                            $visible_items.first().addClass( 'navigation-focus' );
                        }
                }
            });

            $widget.on( 'blur', 'input', function( e ) {
                $( this ).focus();
            } );

            $widget.on( 'mousemove', '', function( e ) {
                $widget.data( 'aucp_last_cursor_pos', [e.pageX, e.pageY] );
            } );

            $widget.on( 'mouseenter', 'ul li', function( e ) {
                var last_pos = $widget.data( 'aucp_last_cursor_pos' );

                if ( last_pos && last_pos[0] == e.pageX && last_pos[1] == e.pageY ) {
                    return;
                }

                $( this ).siblings().removeClass( 'navigation-focus' );
                $( this ).addClass( 'navigation-focus' );
            } );

            $widget.on('click', 'ul li', function(e) {
                var target = $(this).attr('data-code');

                // Save last currency selection.
                $.post( aucp_js.ajaxurl, { action: 'aucp_remember_currency', code: target } );

                // Convert.
                aucp_convert_amounts( target );

                $widget.dialog('close');
            });



            $widget.appendTo( $('body') ).dialog({
                autoOpen: false,
                dialogClass: 'aucp-currency-switcher-container',
                minHeight: 80
            });


            $( 'body' ).on( 'click', '.aucp-currency-amount', function(e) {
                $widget.dialog( 'option', 'position', {
                    my: 'center top+10',
                    at: 'bottom',
                    of: $(this),
                    collision: 'flipfit flipfit'
                } );
                $widget.dialog( 'open' );
                $widget.find( 'input' ).val( '' ).trigger( 'keyup.aucp' ).focus();
            } );

            // Close the switcher when clicking outside of it.
            $( 'body' ).click(function(e) {
                if ( ! $widget.dialog( 'isOpen' ) ) {
                    return;
                }

                var $target = $( e.target );

                if ( $target.closest( '.aucp-currency-amount, .ui-dialog' ).length > 0 ) {
                    return;
                }

                $widget.dialog( 'close' );
            });

            // If there's a default target currency, perform conversion.
            if ( aucp_js.default_target_currency && aucp_js.is_external_api_set) {
                aucp_convert_amounts( aucp_js.default_target_currency );
            }
        });
    })(jQuery);
}


