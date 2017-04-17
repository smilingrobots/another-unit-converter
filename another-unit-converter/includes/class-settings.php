<?php

class AUCP_Settings {

    private $settings = array();


    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function get_settings() {
        $settings = (array) get_option( 'aucp_settings', array() );

        if ( empty( $settings ) ) {
            // Initialize with defaults.
            foreach ( $this->get_registered_settings() as $setting_id => $setting ) {
                if ( ! empty( $setting['default'] ) ) {
                    $settings[ $setting_id ] = $setting['default'];
                }
            }

            update_option( 'aucp_settings', $settings );
        }

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
            'name'        => _x( 'currencylayer API Key', 'settings', 'another-unit-converter' ),
            'description' => '...',
            'type'        => 'text'
        );

        $currencies = array_keys( AUCP()->currencies->get_currencies() );
        $settings['default_currency'] = array(
            'name'        => _x( 'Default Currency', 'settings', 'another-unit-converter' ),
            'description' => '...',
            'type'        => 'select',
            'options'     => array_combine( $currencies, $currencies ),
            'default'     => 'USD'
        );

        // FIXME: this setting name sucks.
        $settings['amount_display'] = array(
            'name'        => _x( 'Conversion display (after choosing a currency)', 'settings', 'another-unit-converter' ),
            'description' => '',
            'type'        => 'select',
            'options'     => array(
                'both'      => _x( 'Display original and converted amounts', 'settings', 'another-unit-converter' ),
                'converted' => _x( 'Display only the converted amount', 'settings', 'another-unit-converter' )
            ),
            'default'     => 'both'
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

        $previous_api_key = $this->get_option( 'currencylayer_key');
        $new_api_key = $new_settings['currencylayer_key'];

        if ( ! empty( $new_api_key ) && $previous_api_key != $new_api_key ) {
            $api_key_valid = false;
            $request = wp_remote_get( 'http://apilayer.net/api/live?access_key=' . $new_api_key . '&source=USD' );

            if ( ! is_wp_error( $request ) ) {
                $response = json_decode( wp_remote_retrieve_body( $request ) );

                if ( $response && isset( $response->success ) && $response->success ) {
                    $api_key_valid = true;
                }
            }

            if ( ! $api_key_valid ) {
                add_settings_error( __( 'Invalid API Key', 'another-unit-converter' ),
                                    'api_key_error',
                                    __( 'Please insert a valid API Key.', 'another-unit-converter' ) );
                $new_settings['currencylayer_key'] = $previous_api_key;
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
