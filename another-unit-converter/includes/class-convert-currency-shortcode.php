<?php

class AUCP_Convert_Currency_Shortcode {

    private $currency_conversion;

    public function __construct( $currency_conversion ) {
        $this->currency_conversion = $currency_conversion;
    }

    public function do_shortcode( $atts, $content = '', $shortcode = '' ) {
        return $this->currency_conversion->convert( $atts['from'], $atts['to'], $atts['amount'] );
    }
}

