<?php

class AUCP_Settings {

    private $settings = array();


    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function get_settings() {
        $settings = (array) get_option( 'aucp_settings', array() );
        return $settings;
    }

    public function get_option( $key = '', $default = false ) {
        if ( empty( $this->settings ) )
            $this->settings = $this->get_settings();

        if ( ! empty( $this->settings[ $key ] ) )
            return $this->settings[ $key ];

        return $default;
    }

    public function update_option( $key, $value ) {
        if ( ! $key ) {
            return false;
        }

        $options = (array) get_option( 'aucp_settings' );
        $options[ $key ] = $value;

        if ( update_option( 'aucp_settings', $options ) ) {
            if ( ! empty( $this->settings ) ) {
                $this->settings[ $key ] = $value;
            }
        }

        return false;
    }

    public function register_settings() {
        add_settings_section( 'aucp_settings', _x( 'General Settings', 'settings', 'another-unit-converter' ), '__return_false', 'aucp_settings' );

        foreach ( $this->get_registered_settings() as $setting_id => $setting_data ) {
            $callback = array( $this, $setting_data['type'] . '_callback' );
            if ( ! is_callable( $callback ) ) {
                $callback = array( $this, 'missing_callback' );
            }

            add_settings_field(
                'aucp_settings[' . $setting_id . ']',
                $setting_data['name'],
                $callback,
                'aucp_settings',
                'aucp_settings',
                array_merge( $setting_data, array( 'id' => $setting_id ) )
            );
        }

        register_setting( 'aucp_settings', 'aucp_settings', array( $this, 'sanitize_settings' ) );
    }

    public function get_registered_settings() {
        $settings = array();

        $settings['enabled'] = array(
            'name'        => _x( 'Enable Another Unit Converter integration?', 'settings', 'another-unit-converter' ),
            'description' => '???',
            'type'        => 'checkbox',
            'default'     => 1
        );

        $settings['currencylayer_key'] = array(
            'name'        => _x( 'Currency Layer API Key', 'settings', 'another-unit-converter' ),
            'description' => '...',
            'type'        => 'text'
        );

        $currencies = array_keys( AUCP()->currencies->get_currencies() );
        $settings['default_currency'] = array(
            'name'        => _x( 'Default Currency', 'settings', 'another-unit-converter' ),
            'description' => '...',
            'type'        => 'select',
            'options'     => array_combine( $currencies, $currencies )
        );

        return apply_filters( 'aucp_registered_settings', $settings );
    }

    public function sanitize_settings( $new_settings ) {
        $new_settings = is_array( $new_settings ) ? $new_settings : array();
        $new_settings = array_merge( $this->get_settings(), $new_settings );

        if ( ! empty( $_POST['_wp_http_referer'] ) ) {
            foreach ( $this->get_registered_settings() as $setting_id => $setting_data ) {
                $type = $setting_data['type'];

                if ( 'checkbox' == $type && array_key_exists( $setting_id, $new_settings ) && $new_settings[ $setting_id ] === '-1' ) {
                    unset( $new_settings[ $setting_id ] );
                }

                if ( empty( $new_settings[ $setting_id ] ) ) {
                    unset( $new_settings[ $setting_id] );
                }
            }
        }

        return $new_settings;
    }

    public function checkbox_callback( $args ) {
        $value = $this->get_option( $args['id'] );
        echo '<input type="hidden" name="aucp_settings[' . $args['id'] . ']" value="-1" />';
        echo '<input type="checkbox" name="aucp_settings[' . $args['id'] . ']" value="1" ' . ( ! empty( $value ) ? checked( 1, $value, false ) : '' ) . '/>';
    }

    public function text_callback( $args ) {
        $value = $this->get_option( $args['id'] );
        echo '<input type="text" name="aucp_settings[' . $args['id'] . ']" value="' . $value . '" />';
    }

    public function select_callback( $args ) {
        $value = $this->get_option( $args['id'] );

        echo '<select name="aucp_settings[' . $args['id'] . ']">';
        foreach ( $args['options'] as $option_value => $option_label ) {
            echo '<option value="' . $option_value . '" ' . selected( $option_value, $value, false ) . '>' . $option_label . '</option>';
        }
        echo '</select>';
    }

    public function missing_callback( $args ) {
        print_r( $args );
    }

}
