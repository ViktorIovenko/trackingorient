document.addEventListener('DOMContentLoaded', function() {
    var maps = {};
    var autoCenterEnabled = {}; // Переменная для хранения состояния автоцентрирования
    var lastLatLng = {}; // Переменная для хранения последней позиции

    // Функция для создания и обновления карты
    function createOrUpdateMap(deviceId, color) {
        console.log('Initializing map for device:', deviceId);
        var mapId = 'map-' + deviceId;
        var mapElement = document.getElementById(mapId);

        if (!mapElement) {
            console.error('Map element not found for device:', deviceId);
            return;
        }

        var map = maps[deviceId];

        if (!map) {
            console.log('Creating new map instance for device:', deviceId);

            // Изначально загружаем карту с мировым обзором
            map = L.map(mapId).setView([20, 0], 2); // Координаты для отображения карты мира
            maps[deviceId] = map;

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // По умолчанию автоцентрирование включено
            autoCenterEnabled[deviceId] = true;

            // Добавление пользовательского контрола для автоцентрирования
            L.Control.AutoCenter = L.Control.extend({
                onAdd: function(map) {
                    var container = L.DomUtil.create('div', 'leaflet-bar leaflet-control leaflet-control-custom');
                    container.style.backgroundColor = 'lightgreen';
                    container.style.width = '50px';
                    container.style.height = '30px';
                    container.style.border = '2px solid black';
                    container.style.borderRadius = '5px';
                    container.style.cursor = 'pointer';
                    container.style.textAlign = 'center';
                    container.style.lineHeight = '30px';
                    container.innerHTML = '<span>Auto</span>';
                    
                    container.onclick = function() {
                        autoCenterEnabled[deviceId] = !autoCenterEnabled[deviceId];
                        container.style.backgroundColor = autoCenterEnabled[deviceId] ? 'lightgreen' : 'white';
                        container.innerHTML = autoCenterEnabled[deviceId] ? '<span>Auto</span>' : '<span>Free</span>';

                        if (autoCenterEnabled[deviceId] && lastLatLng[deviceId]) {
                            // Если включаем автоцентрирование, сразу центрируем карту на последней позиции
                            map.setView(lastLatLng[deviceId], 18, { animate: true });
                            map.dragging.disable();
                            map.touchZoom.disable();
                            map.doubleClickZoom.disable();
                            map.scrollWheelZoom.disable();
                        } else {
                            // Если выключаем автоцентрирование, включаем возможность взаимодействия с картой
                            map.dragging.enable();
                            map.touchZoom.enable();
                            map.doubleClickZoom.enable();
                            map.scrollWheelZoom.enable();
                        }
                    };
                    
                    return container;
                },
                onRemove: function(map) {}
            });

            L.control.autoCenter = function(opts) {
                return new L.Control.AutoCenter(opts);
            };

            L.control.autoCenter({ position: 'topright' }).addTo(map);
        }

        fetch(display_tracking_params.ajax_url + '?action=get_filtered_daily_track&device_id=' + deviceId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Data fetched successfully for device:', deviceId);
                    var locations = data.data;

                    // Удаляем старую полилинию и маркер, если они существуют
                    if (map.polyline) {
                        map.polyline.remove();
                    }
                    if (map.latestMarker) {
                        map.latestMarker.remove();
                    }
                    if (map.deviceLabel) {
                        map.deviceLabel.remove();
                    }

                    if (locations.length > 0) {
                        var latLngs = locations.map(function(location) {
                            return [parseFloat(location.latitude), parseFloat(location.longitude)];
                        });

                        // Добавляем полилинию
                        map.polyline = L.polyline(latLngs, {
                            color: color,
                            weight: 3,
                            opacity: 0.7,
                            dashArray: '5, 10'
                        }).addTo(map);

                        var lastLocation = locations[locations.length - 1];
                        lastLatLng[deviceId] = [parseFloat(lastLocation.latitude), parseFloat(lastLocation.longitude)];

                        var pulsatingIcon = L.divIcon({
                            className: 'pulse-marker',
                            html: '<div class="pulse" style="background-color:' + color + ';"></div>',
                            iconSize: [12, 12]
                        });

                        map.latestMarker = L.marker(lastLatLng[deviceId], {
                            icon: pulsatingIcon
                        }).addTo(map);

                        var label = L.divIcon({
                            className: 'device-label',
                            html: lastLocation.device_id,
                            iconSize: [50, 20]
                        });

                        map.deviceLabel = L.marker(lastLatLng[deviceId], {
                            icon: label,
                            opacity: 0
                        }).addTo(map);

                        map.deviceLabel.setLatLng([lastLatLng[deviceId][0] + 0.0001, lastLatLng[deviceId][1] + 0.0001]);

                        // Если автоцентрирование включено, центрируем карту на последней позиции
                        if (autoCenterEnabled[deviceId]) {
                            map.setView(lastLatLng[deviceId], 18, { animate: true });
                        }

                    } else {
                        console.log('No location data available. Centering map on the world.');
                        if (autoCenterEnabled[deviceId]) {
                            map.setView([20, 0], 2, { animate: true });
                        }
                    }
                } else {
                    console.error('Error fetching locations:', data);
                }
            })
            .catch(error => console.error('Error in AJAX request:', error));
    }

    // Инициализация карт по атрибуту id, начинающемуся с "map-"
    document.querySelectorAll('[id^="map-"]').forEach(function(mapElement) {
        var deviceId = mapElement.getAttribute('data-device-id');
        var color = mapElement.getAttribute('data-color') || 'red';
        console.log('Found map element for device:', deviceId);
        createOrUpdateMap(deviceId, color);
        setInterval(function() {
            createOrUpdateMap(deviceId, color);
        }, 1000); // Обновление каждую секунду
    });
});