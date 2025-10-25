<?php
/*
Plugin Name: PHP Weather Plugin using OpenWeatherMap API
Description: API for waether
Version: 1.0.0
Author: Muryam
License: GPL-2.0+
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
class WeatherPlugin {
    private $apiKey;
    private $apiUrl = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getWeather($city, $units = 'metric') {
        $url = $this->apiUrl . '?q=' . urlencode($city) . '&appid=' . $this->apiKey . '&units=' . $units;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing; enable in production

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            error_log("Weather API Error: " . ($error ?: "HTTP $httpCode"));
            return false;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['main'])) {
            error_log("Invalid JSON from Weather API");
            return false;
        }

        return $data;
    }

    public function displayWeather($weatherData) {
        if (!$weatherData) {
            echo '<p class="weather-error">Unable to fetch weather data. Check your API key or city name.</p>';
            return;
        }

        $city = $weatherData['name'];
        $temp = $weatherData['main']['temp'];
        $description = $weatherData['weather'][0]['description'];
        $humidity = $weatherData['main']['humidity'];
        $icon = $weatherData['weather'][0]['icon'];
        $iconUrl = "https://openweathermap.org/img/wn/{$icon}@2x.png";

        echo "
        <div class='weather-widget' style='border: 1px solid #ccc; padding: 20px; border-radius: 8px; max-width: 300px; text-align: center;'>
            <h3>Weather in {$city}</h3>
            <img src='{$iconUrl}' alt='Weather Icon' style='width: 64px; height: 64px;'>
            <p><strong>Temperature:</strong> {$temp}°C</p>
            <p><strong>Conditions:</strong> {$description}</p>
            <p><strong>Humidity:</strong> {$humidity}%</p>
        </div>";
    }
    public function weatherShortcode($atts) {
        $atts = shortcode_atts([
            'city' => 'London',
            'units' => 'metric',
        ], $atts, 'weather');

        // Validate units
        $validUnits = ['metric', 'imperial', 'standard'];
        $units = in_array($atts['units'], $validUnits) ? $atts['units'] : 'metric';

        // Fetch weather data
        return $this->displayWeather($this->getWeather($atts['city'], $units));
  
       
    }
}
// Example Usage (uncomment and customize)
$plugin = new WeatherPlugin('bdabe763ab4c3757cd2754c5af5148ec');
add_shortcode('weather', [$plugin, 'weatherShortcode']);

// Function to insert the post
function add_post_with_weather_shortcode() {
    // Check if the post already exists to avoid duplicates (optional)
    if (post_exists('Current Weather Update')) {
        error_log('Post already exists.');
        return;
    }

    // Post data
    $post_data = array(
        'post_title'    => 'Current Weather Update',
        'post_content'  => '[weather city="London" units="metric"]', // Customize shortcode here
        'post_status'   => 'publish', // Or 'draft' for review
        'post_type'     => 'post',    // Or 'page' if needed
        'post_author'   => 1,         // Default admin user ID; change as needed
        'post_category' => array(1),  // Array of category IDs; optional
    );

    // Insert the post
    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        error_log('Error creating post: ' . $post_id->get_error_message());
    } else {
        error_log('Post created successfully with ID: ' . $post_id);
    }
}

// Hook to run on admin init (for safety; remove if running manually)
add_action('admin_init', 'add_post_with_weather_shortcode');