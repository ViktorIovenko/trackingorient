<?php
/*
Plugin Name: OwnTracks Data Receiver
Description: Плагин для приема и хранения данных от OwnTracks, с удалением данных старше 7 дней.
Version: 1.2
Author: Iovenko Viktor
*/

// Создание таблицы для хранения данных
function create_owntracks_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'owntracks_locations';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        device_id varchar(255) NOT NULL,
        latitude float(10, 6) NOT NULL,
        longitude float(10, 6) NOT NULL,
        timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        altitude float(10, 6) DEFAULT NULL,
        accuracy float(10, 6) DEFAULT NULL,
        battery int(3) DEFAULT NULL,
        connection varchar(255) DEFAULT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        topic varchar(255) DEFAULT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'create_owntracks_table');

// Удаление данных старше 7 дней
function delete_old_owntracks_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'owntracks_locations';

    // Удаляем данные старше 7 дней
    $wpdb->query("DELETE FROM $table_name WHERE timestamp < DATE_SUB(NOW(), INTERVAL 7 DAY)");
}

// Обработка данных от OwnTracks
add_action('rest_api_init', function () {
    register_rest_route('owntracks/v1', '/location', array(
        'methods' => 'POST',
        'callback' => 'receive_owntracks_data',
        'permission_callback' => '__return_true',
    ));
});

function receive_owntracks_data(WP_REST_Request $request) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'owntracks_locations';

    $data = $request->get_json_params();
    // Логирование для отладки
    error_log("Received raw data: " . print_r($data, true));

    $required_keys = ['tid', 'lat', 'lon'];
    foreach ($required_keys as $key) {
        if (!isset($data[$key])) {
            error_log("Invalid data structure: missing $key");
            return new WP_REST_Response('Invalid data structure', 400);
        }
    }

    $device_id = sanitize_text_field($data['tid']);
    $latitude = floatval($data['lat']);
    $longitude = floatval($data['lon']);
    $altitude = isset($data['alt']) ? floatval($data['alt']) : null;
    $accuracy = isset($data['acc']) ? floatval($data['acc']) : null;
    $battery = isset($data['batt']) ? intval($data['batt']) : null;
    $connection = isset($data['conn']) ? sanitize_text_field($data['conn']) : null;
    $created_at = isset($data['created_at']) ? date('Y-m-d H:i:s', intval($data['created_at'])) : current_time('mysql');
    $topic = isset($data['topic']) ? sanitize_text_field($data['topic']) : null;

    // Удаление старых данных
    delete_old_owntracks_data();

    // Вставка новых данных в таблицу
    $result = $wpdb->insert($table_name, array(
        'device_id' => $device_id,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'timestamp' => current_time('mysql'),
        'altitude' => $altitude,
        'accuracy' => $accuracy,
        'battery' => $battery,
        'connection' => $connection,
        'created_at' => $created_at,
        'topic' => $topic
    ));
    
    if ($result === false) {
        error_log("Failed to insert data into the database: " . $wpdb->last_error);
        return new WP_REST_Response('Database error', 500);
    }

    return new WP_REST_Response('Data received', 200);
}
?>
