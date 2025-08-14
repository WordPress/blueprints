<?php

// Load in a Site Icon.
add_action('after_setup_theme', function () {
    $logo_url = 'https://raw.githubusercontent.com/WordPress/blueprints/refs/heads/trunk/blueprints/welcome/assets/imgs/playground-logo.png';
    $logo_id = get_option('github_imported_logo_id');

    if (!$logo_id || !wp_get_attachment_url($logo_id)) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $response = wp_remote_get($logo_url);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['path'] . '/playground-logo.png';
            file_put_contents($file_path, wp_remote_retrieve_body($response));

            $logo_id = media_handle_sideload(
                [
                    'name'     => 'playground-logo.png',
                    'tmp_name' => $file_path,
                ],
                0
            );

            if (file_exists($file_path)) {
                unlink($file_path);
            }

            if (!is_wp_error($logo_id)) {
                update_option('github_imported_logo_id', $logo_id);
            }
        }
    }

    if ($logo_id) {
        if (!has_custom_logo()) {
            set_theme_mod('custom_logo', $logo_id);
        }
        if (!has_site_icon()) {
            update_option('site_icon', $logo_id);
        }
    }
});

// Update the theme.json file.
function custom_theme_json_theme( $theme_json ){
    $new_data = array(
        'version' => 3,
        'styles' => array(
            'blocks' => array(
                'core/button' => array(
                    'border' => array(
                        'radius' => '2px',
                    ),
                    'typography' => array(
                        'fontFamily'    => 'var(--wp--preset--font-family--manrope)',
                        'textTransform' => 'uppercase',
                        'fontStyle'     => 'normal',
                        'fontWeight'    => '600',
                    ),
                ),
            ),
            'elements' => array(
                'button' => array(
                    'color' => array(
                        'background' => 'var(--wp--preset--color--accent-2)',
                    ),
                    'typography' => array(
                        'textTransform' => 'none',
                    ),
                ),
                'heading' => array(
                    'typography' => array(
                        'fontStyle'     => 'normal',
                        'fontWeight'    => '400',
                        'fontFamily'    => 'var(--wp--preset--font-family--eb-garamond)',
                        'textTransform' => 'none',
                        'lineHeight'    => '1.5',
                        'letterSpacing' => '0px',
                    ),
                ),
            ),
            'color' => array(
                'text' => 'var(--wp--preset--color--accent-4)',
            ),
            'typography' => array(
                'fontStyle'     => 'normal',
                'fontWeight'    => '300',
                'fontSize'      => 'var(--wp--preset--font-size--medium)',
                'letterSpacing' => '0px',
                'fontFamily'    => 'var(--wp--preset--font-family--inter)',
                'lineHeight'    => '1.5',
            ),
            'css' => ' .wp-block-button__link:hover { background-color:var(--wp--preset--color--contrast); color: var(--wp--preset--color--accent-2); } :where(.wp-site-blocks) > footer.wp-block-template-part { margin-block-start: 0; }',
        ),
        'settings' => array(
            'color' => array(
                'palette' => array(
                    'theme' => array(
                        array(
                            'color' => '#FFFFFF',
                            'name'  => 'Base',
                            'slug'  => 'base',
                        ),
                        array(
                            'color' => '#f6f6f6',
                            'name'  => 'Contrast',
                            'slug'  => 'contrast',
                        ),
                        array(
                            'color' => '#33f078',
                            'name'  => 'Accent 1',
                            'slug'  => 'accent-1',
                        ),
                        array(
                            'color' => '#3858e9',
                            'name'  => 'Accent 2',
                            'slug'  => 'accent-2',
                        ),
                        array(
                            'color' => '#e26f56',
                            'name'  => 'Accent 3',
                            'slug'  => 'accent-3',
                        ),
                        array(
                            'color' => '#1a1919',
                            'name'  => 'Accent 4',
                            'slug'  => 'accent-4',
                        ),
                        array(
                            'color' => '#FBFAF3',
                            'name'  => 'Accent 5',
                            'slug'  => 'accent-5',
                        ),
                        array(
                            'color' => 'color-mix(in srgb, currentColor 20%, transparent)',
                            'name'  => 'Accent 6',
                            'slug'  => 'accent-6',
                        ),
                    ),
                ),
            ),
            'typography' => array(
                'fontFamilies' => array(
                    'theme' => array(
                        array(
                            'name'      => 'EB Garamond',
                            'slug'      => 'eb-garamond',
                            'fontFamily'=> '"EB Garamond", serif',
                            'fontFace'  => array(
                                array(
                                    'src'        => array('https://raw.githubusercontent.com/WordPress/blueprints/refs/heads/trunk/blueprints/welcome/assets/fonts/ebgaramond-regular.ttf'),
                                    'fontStyle'  => 'normal',
                                    'fontFamily' => '"EB Garamond"',
                                ),
                            ),
                        ),
                        array(
                            'name'      => 'Inter',
                            'slug'      => 'inter',
                            'fontFamily'=> '"Inter", sans-serif',
                            'fontFace'  => array(
                                array(
                                    'src'        => array('https://raw.githubusercontent.com/WordPress/blueprints/refs/heads/trunk/blueprints/welcome/assets/fonts/inter-regular.ttf'),
                                    'fontStyle'  => 'normal',
                                    'fontFamily' => '"Inter"',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    );

	return $theme_json->update_with( $new_data );
}

add_filter( 'wp_theme_json_data_theme', 'custom_theme_json_theme' );
?>