var count = 0;

if ( typeof jQuery !== 'undefined' && typeof Vue !== 'undefined' ) {
    (function($) {
        Vue.component( 'currency-switcher', {
            props: ['text', 'amount', 'symbol', 'code'],
            template: '' +
            '<div class="another-unit-converter-currency-switcher">' +
            '    <div class="another-unit-converter-currency-switcher-label">{{text}}<a class="another-unit-converter-currency-switcher-button" href="#" v-on:click.prevent="open = !open"></a></div>' +
            '    <div class="another-unit-converter-currency-switcher-widget" v-if="open">' +
            '        <div>Change Currency</div>' +
            '        <ul>' +
            '            <li v-for="currency in currencies"><a href="#" v-on:click.prevent="changeSelectedCurrency(currency)">' +
                             '<span class="another-unit-converter-currency-switcher-widget-symbol">{{ currency.symbol }}</span>' +
                             '<span class="another-unit-converter-currency-switcher-widget-name">{{ currency.name }}</span>' +
                             '<span class="another-unit-converter-currency-switcher-widget-code">({{ currency.code }})</span>' +
            '            </a></li>' +
            '        </ul>' +
            '        <div>Footer</div>' +
            '    </div>' +
            '</div>',
            data: function() {
                return {
                    open: ++count == 1,
                    currencies: [
                        {
                            'name': 'US Dollar',
                            'symbol': '$',
                            'code': 'USD'
                        },{
                            'name': 'Canadian Dollar',
                            'symbol': '$',
                            'code': 'CAD'
                        },{
                            'name': 'Australian Dollar',
                            'symbol': '$',
                            'code': 'AUD'
                        },{
                            'name': 'Euro',
                            'symbol': '$',
                            'code': 'EUR'
                        },{
                            'name': 'Japan Yuan',
                            'symbol': '$',
                            'code': 'JPY'
                        },{
                            'name': 'South Korea Wen',
                            'symbol': '$',
                            'code': 'KRW'
                        }
                    ]
                }
            },

            methods: {
                changeSelectedCurrency: function(currency) {
                    console.log(currency.symbol, currency.name, currency.code);
                }
            }
        } );

        $(function() {
            new Vue({
              el: '#post-8',
              data: {
                message: 'Hello Vue.js!'
              }
            });
            // $( '.another-unit-converter-currency' ).each(function(){});
        });
    })(jQuery);
}
