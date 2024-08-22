<?php
/*
Plugin Name: OwnTracks Live Data Table
Description: Плагин для отображения живой таблицы с данными от OwnTracks.
Version: 1.3
Author: Iovenko Viktor
*/

// Функция для получения последних данных каждого устройства
function get_live_locations() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'owntracks_locations';

    $results = $wpdb->get_results("
        SELECT 
            SUBSTRING_INDEX(topic, '/', -1) as device_name, 
            latitude, 
            longitude, 
            battery, 
            connection, 
            timestamp
        FROM $table_name AS ot
        INNER JOIN (
            SELECT device_id, MAX(timestamp) AS latest_timestamp
            FROM $table_name
            GROUP BY device_id
        ) AS latest ON ot.device_id = latest.device_id AND ot.timestamp = latest.latest_timestamp
        ORDER BY ot.timestamp DESC
    ");

    return $results;
}

// Обработка AJAX-запроса для получения данных
add_action('wp_ajax_nopriv_get_live_locations', 'ajax_get_live_locations');
add_action('wp_ajax_get_live_locations', 'ajax_get_live_locations');

function ajax_get_live_locations() {
    $locations = get_live_locations();
    wp_send_json_success($locations);
}

// Функция для форматирования даты
function format_date($timestamp) {
    return date('j F', strtotime($timestamp));
}

// Вывод таблицы с данными
function display_live_data_table() {
    ob_start();
    ?>
    <table id="live-data-table" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Device Name</th>
                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Time</th>
                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Battery</th>
                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Wi-Fi</th>
                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Latitude</th>
                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Longitude</th>
                <th style="border: 1px solid #000; padding: 8px; text-align: center;">Date</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <script>
        function updateTable() {
            fetch('<?php echo admin_url('admin-ajax.php?action=get_live_locations'); ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        var tbody = document.querySelector('#live-data-table tbody');
                        tbody.innerHTML = '';

                        data.data.forEach(function(location) {
                            var row = document.createElement('tr');

                            // Calculate time since last update
                            var lastUpdate = new Date(location.timestamp);
                            var now = new Date();
                            var diffHours = (now - lastUpdate) / (1000 * 60 * 60); // Difference in hours

                            // Check conditions for styling
                            if (diffHours > 1 || (location.battery && location.battery < 20)) {
                                row.style.backgroundColor = 'red';
                            }

                            // Device Name
                            var cellDeviceName = document.createElement('td');
                            cellDeviceName.style.border = '1px solid #000';
                            cellDeviceName.style.padding = '8px';
                            cellDeviceName.style.textAlign = 'center';
                            cellDeviceName.textContent = location.device_name;
                            row.appendChild(cellDeviceName);

                            // Time
                            var cellTime = document.createElement('td');
                            cellTime.style.border = '1px solid #000';
                            cellTime.style.padding = '8px';
                            cellTime.style.textAlign = 'center';
                            cellTime.textContent = new Date(location.timestamp).toLocaleTimeString();
                            row.appendChild(cellTime);

                            // Battery
                            var cellBattery = document.createElement('td');
                            cellBattery.style.border = '1px solid #000';
                            cellBattery.style.padding = '8px';
                            cellBattery.style.textAlign = 'center';
                            cellBattery.textContent = location.battery ? location.battery + '%' : 'N/A';
                            row.appendChild(cellBattery);

                            // Wi-Fi
                            var cellWifi = document.createElement('td');
                            cellWifi.style.border = '1px solid #000';
                            cellWifi.style.padding = '8px';
                            cellWifi.style.textAlign = 'center';
                            cellWifi.innerHTML = location.connection === 'w' ? '✔️' : '❌';
                            row.appendChild(cellWifi);

                            // Latitude
                            var cellLatitude = document.createElement('td');
                            cellLatitude.style.border = '1px solid #000';
                            cellLatitude.style.padding = '8px';
                            cellLatitude.style.textAlign = 'center';
                            cellLatitude.textContent = location.latitude;
                            row.appendChild(cellLatitude);

                            // Longitude
                            var cellLongitude = document.createElement('td');
                            cellLongitude.style.border = '1px solid #000';
                            cellLongitude.style.padding = '8px';
                            cellLongitude.style.textAlign = 'center';
                            cellLongitude.textContent = location.longitude;
                            row.appendChild(cellLongitude);

                            // Date
                            var cellDate = document.createElement('td');
                            cellDate.style.border = '1px solid #000';
                            cellDate.style.padding = '8px';
                            cellDate.style.textAlign = 'center';
                            cellDate.textContent = new Date(location.timestamp).toLocaleDateString('ru-RU', { day: 'numeric', month: 'long' });
                            row.appendChild(cellDate);

                            tbody.appendChild(row);
                        });
                    }
                });
        }

        setInterval(updateTable, 1000); // Обновление таблицы каждые 1 секунду
        document.addEventListener('DOMContentLoaded', updateTable);
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('display_live_data_table', 'display_live_data_table');
?>
