<?php
/**
 * Plugin Name: OpenWeather Shortcode
 * Description: Displays current weather via shortcode [ows_weather city="YourCity" api_key="your-key"] with beautiful styling.
 * Version: 1.2
 * Author: Muryam
 * Text Domain: openweather-shortcode
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'OWS_Weather_Shortcode' ) ) {

    class OWS_Weather_Shortcode {
        private $api_key_option = 'ows_api_key';

        public function __construct() {
            add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
            add_shortcode( 'ows_weather', [ $this, 'shortcode_handler' ] );
        }

        // Add settings page
        public function add_settings_page() {
            add_options_page(
                __( 'OpenWeatherAPI Settings', 'openweather-shortcode' ),
                __( 'OpenWeatherAPI', 'openweather-shortcode' ),
                'manage_options',
                'ows-settings',
                [ $this, 'settings_page_html' ]
            );
        }

        // Settings page HTML
        public function settings_page_html() {
            // Security check: capability
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            // Process form submission
            if ( isset( $_POST['ows_api_key'] ) ) {
                // Security check: nonce verification
                if ( ! isset( $_POST['ows_settings_nonce'] ) || ! wp_verify_nonce( $_POST['ows_settings_nonce'], 'ows_save_settings' ) ) {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Security check failed. Please try again.', 'openweather-shortcode' ) . '</p></div>';
                } else {
                    update_option( $this->api_key_option, sanitize_text_field( $_POST['ows_api_key'] ) );
                    echo '<div class="notice notice-success"><p>' . esc_html__( 'API Key saved!', 'openweather-shortcode' ) . '</p></div>';
                }
            }

            $key = get_option( $this->api_key_option, '' );
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'OpenWeatherAPI Settings', 'openweather-shortcode' ); ?></h1>
                <form method="post">
                    <?php wp_nonce_field( 'ows_save_settings', 'ows_settings_nonce' ); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e( 'API Key', 'openweather-shortcode' ); ?></th>
                            <td>
                                <input type="text" name="ows_api_key" value="<?php echo esc_attr( $key ); ?>" class="regular-text" />
                                <p class="description"><?php printf( esc_html__( 'Enter your OpenWeatherMap API key (get one at %s).', 'openweather-shortcode' ), '<a href="https://openweathermap.org" target="_blank">openweathermap.org</a>' ); ?></p>
                                <p class="description"><?php esc_html_e( 'Alternatively, you can pass the API key directly in the shortcode:', 'openweather-shortcode' ); ?> <code>[ows_weather city="London" api_key="your-key"]</code></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }

        // Shortcode handler
        public function shortcode_handler( $atts ) {
            $atts = shortcode_atts( [
                'city'    => 'London',
                'api_key' => ''
            ], $atts, 'ows_weather' );

            $city = sanitize_text_field( $atts['city'] );

            // Prioritize shortcode api_key, fallback to stored option
            $key = ! empty( $atts['api_key'] )
                ? sanitize_text_field( $atts['api_key'] )
                : get_option( $this->api_key_option, '' );

            if ( empty( $key ) ) {
                return '<p class="ows-error">' . esc_html__( 'OpenWeatherAPI Error: Please provide an API key via shortcode [ows_weather city="YourCity" api_key="your-key"] or in Settings > OpenWeatherAPI.', 'openweather-shortcode' ) . '</p>';
            }

            $transient_key = 'ows_weather_' . md5( $city );
            $cached = get_transient( $transient_key );

            if ( $cached !== false ) {
                return $cached;
            }

            $url = 'https://api.openweathermap.org/data/2.5/weather?q=' . urlencode( $city ) . '&appid=' . $key . '&units=metric';
            $response = wp_remote_get( $url, [ 'timeout' => 10 ] );

            if ( is_wp_error( $response ) ) {
                return '<p class="ows-error">' . esc_html__( 'OpenWeatherAPI Error: Unable to fetch data. Please try again later.', 'openweather-shortcode' ) . '</p>';
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );

            if ( isset( $data['cod'] ) && $data['cod'] != 200 ) {
                return '<p class="ows-error">' . esc_html__( 'OpenWeatherAPI Error: ', 'openweather-shortcode' ) . esc_html( $data['message'] ) . '</p>';
            }

            if ( ! isset( $data['main'] ) || ! isset( $data['weather'] ) ) {
                return '<p class="ows-error">' . esc_html__( 'OpenWeatherAPI Error: Unable to fetch data. Please try again later.', 'openweather-shortcode' ) . '</p>';
            }

            $condition = $data['weather'][0]['description'];
            $icon = 'https://openweathermap.org/img/wn/' . $data['weather'][0]['icon'] . '@2x.png';
            $temp_c = round( $data['main']['temp'] );
            $feelslike_c = round( $data['main']['feels_like'] );
            $wind_kph = round( $data['wind']['speed'] * 3.6 ); // Convert m/s to km/h
            $humidity = $data['main']['humidity'];
            $last_updated = date( 'Y-m-d H:i', $data['dt'] );

            $output = '
            <div class="ows-card">
                <h3 class="ows-title">' . esc_html__( 'Weather in', 'openweather-shortcode' ) . ' ' . esc_html( $city ) . '</h3>
                <div class="ows-main">
                    <img src="' . esc_url( $icon ) . '" alt="' . esc_attr( $condition ) . '" class="ows-icon" />
                    <div class="ows-temp">' . esc_html( $temp_c ) . '&deg;C</div>
                </div>
                <p class="ows-condition">' . esc_html( ucfirst( $condition ) ) . '</p>
                <div class="ows-details">
                    <p><span class="ows-label">' . esc_html__( 'Feels Like:', 'openweather-shortcode' ) . '</span> ' . esc_html( $feelslike_c ) . '&deg;C</p>
                    <p><span class="ows-label">' . esc_html__( 'Wind:', 'openweather-shortcode' ) . '</span> ' . esc_html( $wind_kph ) . ' km/h</p>
                    <p><span class="ows-label">' . esc_html__( 'Humidity:', 'openweather-shortcode' ) . '</span> ' . esc_html( $humidity ) . '%</p>
                </div>
                <small class="ows-updated">' . esc_html__( 'Last updated:', 'openweather-shortcode' ) . ' ' . esc_html( $last_updated ) . '</small>
            </div>';

            set_transient( $transient_key, $output, 600 );
            return $output;
        }
    }

    // Instantiate the class
    new OWS_Weather_Shortcode();
}
