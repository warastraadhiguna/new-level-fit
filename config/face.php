<?php
return [
    'attendance_url' => env('FACE_ATTENDANCE_URL', 'http://localhost:8000/activate'),
    'url' => env('FACE_SERVICE_URL', 'http://127.0.0.1:8001'),
    'timeout' => (int) env('FACE_SERVICE_TIMEOUT', 30),
    'model' => 'opencv-sface-2021dec',
    'database' => env('FACE_DB_DATABASE', 'level-fit'),
];
