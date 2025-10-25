<?php
/**
 * Simple PHP Weather Plugin using OpenWeatherMap API
 * 
 * @author Muryam
 * @version 1.0
 */

class WeatherPlugin {
    private $apiKey;
    private $apiUrl = 'https://api.openweathermap.org/data/2.5/weather';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * Fetch and display current weather for a city.
     * 
     * @param string $city The city name (e.g., 'London')
     * @param string $units Units: 'metric' (Celsius), 'imperial' (Fahrenheit), or 'standard' (Kelvin). Default: 'metric'
     * @return array|bool Weather data array or false on error
     */
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

    /**
     * Display weather in a simple HTML format.
     * 
     * @param array $weatherData The data from getWeather()
     */
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
}
add_shortcode('weather', [$plugin, 'handleShortcode']);


// Example Usage (uncomment and customize)
// $plugin = new WeatherPlugin('YOUR_API_KEY_HERE');
// $weather = $plugin->getWeather('London');
// $plugin->displayWeather($weather);
?>