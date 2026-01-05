<?php
/**
 * Plugin Name: OpenWeather Shortcode
 * Description: Displays current weather via shortcode [weather city="YourCity"] with beautiful styling.
 * Version: 1.0
 * Author: Muryam
 * Text Domain: openweather-shortcode
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class OpenWeather_Shortcode {
    private $api_key_option = 'openweather_api_key';
    private $key =  'bdabe763ab4c3757cd2754c5af5148ec';

    public function __construct() {
        add_action('after_setup_theme', [$this, 'add_key']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_shortcode('weather', [$this, 'shortcode_handler']);

    }
    //add default key
    public function add_key(){
        if (!get_option($this->api_key_option)) {
            add_option($this->api_key_option, $this->key);
        }
    }
    
    // Add settings page
    public function add_settings_page() {
        add_options_page(
            'OpenWeatherAPI Settings',
            'OpenWeatherAPI',
            'manage_options',
            'openweather-settings',
            [$this, 'settings_page_html']
        );
    }

    // Settings page HTML
    public function settings_page_html() {
        if (isset($_POST['openweather_api_key'])) {
            update_option($this->api_key_option, sanitize_text_field($_POST['openweather_api_key']));
            echo '<div class="notice notice-success"><p>API Key saved!</p></div>';
        }
        $key = get_option($this->api_key_option, '');
        ?>
        <div class="wrap">
            <h1>OpenWeatherAPI Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="text" name="openweather_api_key" value="<?php echo esc_attr($key); ?>" class="regular-text" />
                            <p class="description">Enter your OpenWeatherMap API key (get one at <a href="https://openweathermap.org" target="_blank">openweathermap.org</a>).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    // Shortcode handler
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(['city' => 'London'], $atts);
        $city = sanitize_text_field($atts['city']);
        $key = get_option($this->api_key_option, '');

        if (empty($key)) {
            return '<p class="openweather-error">OpenWeatherAPI Error: Please enter your API key in Settings > OpenWeatherAPI.</p>';
        }

        $transient_key = 'openweather_' . md5($city);
        $cached = get_transient($transient_key);

        if ($cached !== false) {
            return $cached;
        }

        $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&appid={$key}&units=metric";
        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response)) {
            return '<p class="openweather-error">'. __('OpenWeatherAPI Error: Unable to fetch data. Please try again later.', 'openweather-shortcode') . '</p>';       
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['cod']) && $data['cod'] != 200) {
            return '<p class="openweather-error">'.__('OpenWeatherAPI Error: ') . esc_html($data['message']) . '</p>';
        }

        if (!isset($data['main']) || !isset($data['weather'])) {
            return '<p class="openweather-error">'. __('OpenWeatherAPI Error: Unable to fetch data. Please try again later.', 'openweather-shortcode') . '</p>';        
        }

        $condition = $data['weather'][0]['description'];
        $icon = "https://openweathermap.org/img/wn/" . $data['weather'][0]['icon'] . "@2x.png";
        $temp_c = round($data['main']['temp']);
        $feelslike_c = round($data['main']['feels_like']);
        $wind_kph = round($data['wind']['speed'] * 3.6); // Convert m/s to km/h
        $humidity = $data['main']['humidity'];
        $last_updated = date('Y-m-d H:i', $data['dt']);

        $output = '
        <div class="openweather-card">
            <h3 class="openweather-title">Weather in ' . esc_html($city) . '</h3>
            <div class="openweather-main">
                <img src="' . esc_url($icon) . '" alt="' . esc_attr($condition) . '" class="openweather-icon" />
                <div class="openweather-temp">' . esc_html($temp_c) . '&deg;C</div>
            </div>
            <p class="openweather-condition">' . esc_html(ucfirst($condition)) . '</p>
            <div class="openweather-details">
                <p><span class="openweather-label">Feels Like:</span> ' . esc_html($feelslike_c) . '&deg;C</p>
                <p><span class="openweather-label">Wind:</span> ' . esc_html($wind_kph) . ' km/h</p>
                <p><span class="openweather-label">Humidity:</span> ' . esc_html($humidity) . '%</p>
            </div>
            <small class="openweather-updated">Last updated: ' . esc_html($last_updated) . '</small>
        </div>';

        set_transient($transient_key, $output, 600);
        return $output;
    }
}

// Instantiate the class
new OpenWeather_Shortcode();
?>