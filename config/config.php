<?php
return [
    'app_name' => 'Digital Signage System',
    'timezone' => 'Asia/Bangkok',
    'max_upload_size' => 104857600,
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'mp3', 'wav', 'html'],
    'upload_path' => 'uploads/',
    'jwt_secret' => '8558a167a1ae5d33ba552a620b61b8c7422e6fd9acc1358c313ae98cb4e729a9',
    'session_timeout' => 3600,
    'debug' => false,
    'log_level' => 'info',
    'enable_analytics' => true,
    'heartbeat_interval' => 30,
    'default_content_duration' => 10,
];
?>