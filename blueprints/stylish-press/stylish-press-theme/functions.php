<?php
function stylish_press_enqueue_styles() {
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Open+Sans:wght@400;700&display=swap', false);
}

add_action('wp_enqueue_scripts', 'stylish_press_enqueue_styles');
