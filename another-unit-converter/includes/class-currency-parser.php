<?php

class AUCP_Currency_Parser {

    private $currencies;

    public function __construct( $currencies ) {
        $this->currencies = $currencies;
    }

    public function get_currency_amounts( $content ) {
        $content_without_tags = strip_tags( $content );
        $regexp = '/(*UTF8)(?<currency_amount>\d{4,}|\d{1,3}(?:[,. ]\d{1,3})*)/';

        if ( ! preg_match_all( $regexp, $content_without_tags, $matches, PREG_OFFSET_CAPTURE ) ) {
            return array();
        }

        $currency_amounts = array();

        foreach ( $matches['currency_amount'] as $match ) {
            $offset = mb_strlen( mb_strcut( $content_without_tags, 0, $match[1] ) );
            $amount_text = $match[0];

            $prefix = mb_substr( $content_without_tags, max( 0, $offset - 9 ), min( $offset, 9 ) );
            $prefix_parts = array_filter( explode( "\n", $prefix ) );
            $closest_prefix = trim( preg_replace( '/\s+/', ' ', array_pop( $prefix_parts ) ) );
            $prefix_parts = array_reverse( explode( ' ', $closest_prefix ) );

            $suffix = mb_substr( $content_without_tags, $offset + strlen( $amount_text ), 9 );
            $suffix_parts = array_filter( explode( "\n", $suffix ) );
            $closest_suffix = trim( preg_replace( '/\s+/', ' ', array_shift( $suffix_parts ) ) );
            $suffix_parts = explode( ' ', $closest_suffix );

            $parts = array();
            $max_number_of_parts = max( count( $prefix_parts ), count( $suffix_parts ) );

            for ( $i = 0; $i < $max_number_of_parts; $i++ ) {
                if ( isset( $prefix_parts[ $i ] ) ) {
                    $parts[] = $prefix_parts[ $i ];
                }

                if ( isset( $suffix_parts[ $i ] ) ) {
                    $parts[] = $suffix_parts[ $i ];
                }
            }

            $currencies = $this->get_currencies_from_parts( $parts );

            if ( ! $currencies ) {
                continue;
            }

            $currency_amounts[] = array(
                'amount_text' => $amount_text,
                'currencies' => $this->prepare_currencies( $amount_text, $currencies ),
            );
        }

        return $currency_amounts;
    }

    private function get_currencies_from_parts( $parts, $part_index = 0 ) {
        $currencies = array();

        $number_of_parts = count( $parts );

        for ( $i = $part_index; $i < $number_of_parts; $i = $i + 1 ) {
            $part_without_punctuation = preg_replace( '/[\(\)\.:;,<=>\|!~\?_\-]/', '', $parts[ $i ] );

            /**
             * Capturing Groups:
             *
             * currency_code: 1,6
             * country_code: 3,8
             * currency_symbol: 2,4,5,7,9
             *
             * XXX: Symbol regex: [^\s]{0,2}[^\d\s] or [^\d\s]{1,3}
             */
            preg_match(
                    '/(*UTF8)'
                .       '([[:alpha:]]{3})([^\s]{0,2}[^\d\s])?' // Matches USD or USD$
                . '|' . '([[:alpha:]]{2})([^\s]{0,2}[^\d\s])?' // Matches US or US$
                . '|' . '([^\s]{0,2}[^\d\s])([[:alpha:]]{3})'  // Matches $USD
                . '|' . '([^\s]{0,2}[^\d\s])([[:alpha:]]{2})'  // Matches $US
                . '|' . '([^\s]{0,2}[^\d\s])'                  // Matches $
                .   '/',
                $part_without_punctuation,
                $captured_values
            );

            $currency_code = $this->get_captured_values( $captured_values, array( 1, 6 ) );
            $country_code = $this->get_captured_values( $captured_values, array( 3, 8 ) );
            $currency_symbol = $this->get_captured_values( $captured_values, array( 2, 4, 5, 7, 9 ) );

            // some symbols are just three letters (Lek symbol for ALL)
            if ( ! $currency_symbol && $currency_code ) {
                $currency_symbol = $currency_code;
            }

            // some symbols are just two letters ('kr' symbol for SEK)
            if ( ! $currency_symbol && $country_code ) {
                $currency_symbol = $country_code;
            }

            if ( $currency_code ) {
                $currency = $this->currencies->get_currency( $currency_code );
                $currencies = $currency ? array( $currency ) : array();
            }

            if ( ! $currencies && $country_code ) {
                $currency = $this->currencies->get_currency_from_country_code( $country_code );
                $currencies = $currency ? array( $currency ) : array();
            }

            if ( ! $currencies && $currency_symbol ) {
                $currencies = $this->currencies->find_currencies_by_symbol( $currency_symbol );

                if ( count( $currencies ) > 1 ) {
                    $suggested_currencies = $this->get_currencies_from_parts( $parts, $part_index + 1 );
                    $currencies = $this->try_to_choose_currency( $currencies, $suggested_currencies );
                }
            }

            if ( $currencies ) {
                break;
            }
        }

        return $currencies;
    }

    private function get_captured_values( $matches, $alternatives ) {
        foreach ( $alternatives as $key ) {
            if ( isset( $matches[ $key ] ) && $matches[ $key ] ) {
                return $matches[ $key ];
            }
        }
    }

    private function try_to_choose_currency( $currencies, $suggested_currencies ) {
        if ( 1 === count( $suggested_currencies ) ) {
            foreach ( $currencies as $currency ) {
                if ( $currency['code'] == $suggested_currencies[0]['code'] ) {
                    return array( $suggested_currencies[0] );
                }
            }
        }

        return $currencies;
    }

    private function prepare_currencies( $amount_text, $currencies ) {
        $prepared_currencies = array();

        foreach ( $currencies as $i => $currency ) {
            $prepared_currencies[] = array(
                'currency' => $currency,
                'pattern' => $this->get_currency_pattern( $amount_text, $currency ),
                'amount' => $this->parse_amount( $amount_text, $currency ),
            );
        }

        return $prepared_currencies;
    }

    private function get_currency_pattern( $amount_text, $currency ) {
        $replacements = array(
            '<currency-code>' => $currency['code'],
            '<currency-symbol>' => '(?:' . preg_quote( $currency['symbol'] ) . ')',
            // TODO: the first two characters of the code not always match the country code
            '<country-code>' => substr( $currency['code'], 0, 2 ),
            '<amount>' => preg_quote( $amount_text ),
        );

        $pattern_parts = array(
            '<currency-code> *<currency-symbol>? *<amount>',
            '<country-code> *<currency-symbol>? *<amount>',
            '<currency-symbol> *<currency-code> *<amount>',
            '<currency-symbol> *<country-code> *<amount>',
            '<currency-symbol> *<amount> *<currency-code>',
            '<amount> *<currency-code> *<currency-symbol>',
            '<amount> *<country-code> *<currency-symbol>',
            '<amount> *<currency-symbol> *<currency-code>',
            '<amount> *<currency-symbol> *<country-code>',
            '<currency-symbol> *<amount>',
            '<amount> *<currency-code>',
            '<amount> *<country-code>',
            '<amount> *<currency-symbol>'
        );

        $pattern = implode( '|', $pattern_parts );
        $pattern = str_replace( array_keys( $replacements ), array_values( $replacements ), $pattern );

        return '/(*UTF8)' . $pattern . '/';
    }

    public function parse_amount( $amount_text, $currency ) {
        if ( $currency['decimal_point'] ) {
            $amount_parts = explode( $currency['decimal_point'], $amount_text );
        } else {
            $amount_parts = array( $amount_text );
        }

        $amount_integer = preg_replace( '/[^0-9]/', '', $amount_parts[0] );
        $amount_decimals = isset( $amount_parts[1] ) ? $amount_parts[1] : '0';

        return intval( $amount_integer ) + floatval( '0.' . $amount_decimals );
    }
}
