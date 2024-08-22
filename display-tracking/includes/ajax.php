<?php

// Обработка AJAX-запроса для получения трека за последние сутки
add_action('wp_ajax_nopriv_get_filtered_daily_track', 'ajax_get_filtered_daily_track');
add_action('wp_ajax_get_filtered_daily_track', 'ajax_get_filtered_daily_track');

function ajax_get_filtered_daily_track() {
    $device_id = isset($_GET['device_id']) ? intval($_GET['device_id']) : 1;
    $locations = get_filtered_daily_track($device_id);
    wp_send_json_success($locations);
}
?>
