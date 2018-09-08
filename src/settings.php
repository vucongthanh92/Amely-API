<?php
define("IMAGE_URL", "https://userdata.amely.com");
define("IMAGE_PATH", "/home/amelywebmaster/ossn_userdata");
define("AVATAR_DEFAULT", "https://i1.wp.com/grueneroadpharmacy.com/wp-content/uploads/2017/02/user-placeholder-1.jpg");
define("COVER_DEFAULT", "https://i1.wp.com/grueneroadpharmacy.com/wp-content/uploads/2017/02/user-placeholder-1.jpg");

return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        "db" => [
            "host" => "localhost",
            "dbname" => "amely",
            "user" => "root",
            "pass" => "root"
        ],
        "url" => "https://amely.com"
    ],
];
