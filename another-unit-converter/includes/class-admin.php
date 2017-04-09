<?php

class AUCP_Admin {

    public function __construct() {
        if ( ! is_admin() )
            return;

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    public function admin_menu() {
        add_options_page(
            _x( 'Another Unit Converter - Settings', 'admin menu', 'another-unit-converter' ),
            _x( 'Another Unit Converter', 'admin menu', 'another-unit-converter' ),
            'administrator',
            'aucp_settings',
            array( $this, 'settings_page' )
        );
    }

    public function settings_page() {
        echo '<div class="wrap">';
        echo '<h1>' . get_admin_page_title() . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'aucp_settings' );
        do_settings_sections( 'aucp_settings' );
        submit_button();
        echo '</form>';
        echo '</div>';
    }

}
