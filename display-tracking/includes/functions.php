<?php

// Функция для фильтрации точек
function filter_points($locations, $radius_m = (8), $time_window_s = 3600) { // 1800 секунд = 30 минут
    $filtered = [];
    $last_point = null;

    foreach ($locations as $location) {
        if ($last_point === null) {
            $last_point = $location;
            $filtered[] = $last_point;
            continue;
        }

        $distance = haversine_distance(
            $last_point->latitude, $last_point->longitude,
            $location->latitude, $location->longitude
        );

        $time_diff = strtotime($location->timestamp) - strtotime($last_point->timestamp);

        // Если точка вне радиуса или прошло больше 30 минут, добавляем новую точку
        if ($distance > $radius_m || $time_diff > $time_window_s) {
            $filtered[] = $location;
            $last_point = $location;
        } else {
            // Если точка в радиусе и в пределах 30 минут, обновляем последнюю точку
            $filtered[count($filtered) - 1] = $location;
            $last_point = $location;
        }
    }

    return $filtered;
}

// Функция для вычисления расстояния между двумя точками по Хаверсину
function haversine_distance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // Радиус Земли в метрах
    $lat1 = deg2rad($lat1);
    $lon1 = deg2rad($lon1);
    $lat2 = deg2rad($lat2);
    $lon2 = deg2rad($lon2);

    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;

    $a = sin($dlat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dlon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earth_radius * $c;
}

// Функция для получения трека за последние сутки
function get_filtered_daily_track($device_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'owntracks_locations';

    // Определение начала текущего дня
    $start_of_day = date('Y-m-d 00:00:00');

    $results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            device_id,
            latitude, 
            longitude, 
            timestamp
        FROM $table_name
        WHERE timestamp >= %s AND device_id = %d
        ORDER BY timestamp ASC
    ", $start_of_day, $device_id));

    // Фильтрация точек
    return filter_points($results);
}
?>
<?php