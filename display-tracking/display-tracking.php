<?php
/*
Plugin Name: Display Tracking
Description: Плагин для отображения трека за последние 24 часа с пульсирующим маркером и фильтрацией точек.
Version: 1.0
Author: Iovenko Viktor
*/

// Подключаем файлы плагина
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax.php';

// Регистрация стилей и скриптов
function display_tracking_enqueue_assets() {
    // Подключаем стили Leaflet
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css');
    
    // Подключаем скрипты Leaflet
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', array(), null, true);
    
    // Подключаем стили и скрипты вашего плагина
    wp_enqueue_style('display-tracking-style', plugin_dir_url(__FILE__) . 'css/style.css');
    wp_enqueue_script('display-tracking-map', plugin_dir_url(__FILE__) . 'js/map.js', array('jquery', 'leaflet-js'), null, true);

    // Локализация скрипта с переменными
    wp_localize_script('display-tracking-map', 'display_tracking_params', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'display_tracking_enqueue_assets');

// Регистрация уникального шорткода
function display_tracking_shortcode($atts) {
    $atts = shortcode_atts(array(
        'device_id' => '1',
        'color' => 'red'
    ), $atts, 'display_tracking_map');

    ob_start();
    ?>
    <div id="map-<?php echo esc_attr($atts['device_id']); ?>" 
         data-device-id="<?php echo esc_attr($atts['device_id']); ?>"
         data-color="<?php echo esc_attr($atts['color']); ?>"
         style="height: 500px; width: 100%;"></div>
    <div id="gadget-info"></div>
    <?php
    return ob_get_clean();
}
add_shortcode('display_tracking_map', 'display_tracking_shortcode');
?>