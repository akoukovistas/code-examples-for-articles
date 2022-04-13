<?php

require_once 'includes/CurrentVersionEndpoint.php';

new CurrentVersionEndpoint();

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @param $sections
 * @return mixed
 */
function cwc_wc_add_custom_advanced_section($sections): array
{

    $sections['custom_api_values'] = __('Custom API values', 'cwc_wc_custom_api_settings');
    return $sections;

}

add_filter('woocommerce_get_sections_advanced', 'cwc_wc_add_custom_advanced_section');

/**
 * @param $settings
 * @param $current_section
 * @return array
 */
function cwc_wc_register_api_version_option($settings, $current_section): array
{

    if ($current_section == 'custom_api_values') {
        $custom_api_settings = [];
        $custom_api_settings[] = [
            'name' => __('Stable app version:', 'cwc_wc_api_number'),
            'desc_tip' => __('Sets the current stable version of the app.', 'cwc_wc_api_number'),
            'id' => 'cwc_wc_stable_app_version',
            'type' => 'text',
            'css' => 'min-width:100px;',
            'std' => '',  // WC < 2.0
            'default' => '',  // WC >= 2.0
            'desc' => __('Add the version here.', 'cwc_wc_api_number'),
        ];

        $custom_api_settings[] = array('type' => 'sectionend', 'id' => 'wcslider');
        return $custom_api_settings;
    }

    return $settings;
}

add_filter('woocommerce_get_settings_advanced', 'cwc_wc_register_api_version_option', 10, 2);