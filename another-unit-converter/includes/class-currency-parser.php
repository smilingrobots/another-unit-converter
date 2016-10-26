<?php

class AUCP_Currency_Parser {

    private $currencies;

    public function __construct( $currencies ) {
        $this->currencies = $currencies;
    }

    public function parse_amount( $amount_text, $amount_symbol, $amount_number ) {
        $currency_code = $this->get_currency_code( $amount_symbol );

        if ( is_null( $currency_code ) ) {
            return null;
        }

        $currency = $this->currencies->get_currency( $currency_code );

        if ( is_null( $currency ) ) {
            return null;
        }

        $amount_parts = explode( $currency['decimal_point'], $amount_number );
        $amount_integer = preg_replace( '/[^0-9]/', '', $amount_parts[0] );
        $amount_decimals = isset( $amount_parts[1] ) ? $amount_parts[1] : 0;

        return array(
            'amount' => intval( $amount_integer ) + floatval( '0.' . $amount_decimals ),
            'original_amount' => $amount_number,
            'symbol' => $currency['symbol'],
            'code' => $currency['code'],
        );
    }

    private function get_currency_code( $amount_symbol ) {
        $currency_code = $this->currencies->get_currency_code_from_symbol( $amount_symbol );

        if ( is_null( $currency_code ) ) {
            $country_code = substr( $amount_symbol, 0, 2 );
            $currency_code = $this->currencies->get_currency_code_from_country_code( $country_code );
        }

        if ( is_null( $currency_code ) ) {
            $country_code = substr( $amount_symbol, -2 );
            $currency_code = $this->currencies->get_currency_code_from_country_code( $country_code );
        }

        return $currency_code ? $currency_code : null;
    }
}
