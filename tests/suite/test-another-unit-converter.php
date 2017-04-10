<?php

class AUCP_Test_Another_Unit_Converter extends AUCP_Test_Case {

    public function test_format_currency_amounts() {
        $this->currency_parser = Phake::mock( 'AUCP_Currency_Parser' );

        Phake::when( $this->currency_parser )->get_currency_amounts->thenReturn( array( array(
            'amount_text' => '$ 2500',
            'currencies' => array(
                'USD' => array(),
                'AUD' => array(),
                'COP' => array(
                    'currency' => array(
                        'name' => 'Colombian Peso',
                        'code' => 'COP',
                        'symbol' => '$',
                        'decimal_places' => '2',
                        'display_format' => '#.###,##',
                        'thousands_separator' => '.',
                        'decimal_point' => ',',
                    ),
                    'position' => array( 'start' => 0, 'end' => 6 ),
                    'amount' => 2500,
                ),
                'ARS' => array(),
            )
        ) ) );

        $this->settings = Phake::mock( 'AUCP_Settings' );

        Phake::when( $this->settings )->get_option( 'default_currency' )->thenReturn( 'COP' );

        $plugin = new Another_Unit_Converter_Plugin(
            $this->currency_parser, null, null, $this->settings, null
        );

        $formatted_content = $plugin->format_currency_amounts( '$ 2500' );

        $this->assertContains( 'data-unit-converter-currency-code="COP"', $formatted_content );
    }
}
