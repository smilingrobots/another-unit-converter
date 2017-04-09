if ( typeof jQuery !== 'undefined' ) {
    (function($) {
        $(function() {
            var $widget = $( $('#aucp-currency-switcher-template').html() );

            $widget.appendTo( $('body') ).dialog({
                autoOpen: false,
                dialogClass: 'aucp-currency-switcher-container',
                minHeight: 80
            });

            var ENTER = 13,
                SPACE = 32,
                UP = 38,
                DOWN = 40,
                RIGHT = 39,
                LEFT = 37;

            $widget.on('keyup.aucp', 'input', function(e) {
                switch ( e.keyCode ) {
                    case ENTER:
                    case SPACE:
                        break;
                    case UP:
                        break;
                    case DOWN:
                        break;
                    default:
                        var search = $(this).val().toLowerCase();
                        var $allCurrencies = $widget.find('[data-content]').show();

                        if ( search.length ) {
                            $allCurrencies.not('[data-content*="' + search + '"]').hide();
                        }
                }
            });

            $widget.on('click', 'ul li', function(e) {
                var target = $(this).attr('data-code');
                var sources = $('[data-unit-converter-currency-code]');
                var codes = sources.map(function() {
                    return $(this).attr('data-unit-converter-currency-code');
                }).get();

                $.getJSON($widget.attr('data-ajax-url'), {
                    action: 'aucp_get_rates',
                    codes: $.unique(codes.concat(['USD', target]))
                }).done(function(data) {
                    sources.each(function() {
                        var $currencyAmount = $(this),
                            amount = $currencyAmount.attr('data-unit-converter-currency-amount'),
                            code = $currencyAmount.attr('data-unit-converter-currency-code'),
                            symbol = $currencyAmount.attr('data-unit-converter-currency-symbol'),
                            fromRate = data.rates[code],
                            toRate = data.rates[target],
                            newAmount = toRate * (parseFloat(amount) / fromRate),
                            // http://www.jacklmoore.com/notes/rounding-in-javascript/
                            roundedAmount = Number(Math.round(newAmount+'e2')+'e-2');

                        $currencyAmount.html(roundedAmount + ' ' + target);
                    })
                });

                $widget.dialog('close');
            });

            $search = $widget.find('input');

            $('.aucp-currency-amount').each(function() {
                $(this).click(function() {
                    $search.val('').trigger('keyup.aucp').focus();

                    $widget.dialog('option', 'position', { my: 'center top+10', at: 'bottom', of: $(this) });
                    $widget.dialog('open');
                });
            });
        });
    })(jQuery);
}
