<?php
define("IMAGE_URL", "https://userdata.amely.com");
define("IMAGE_PATH", "/home/thinhnez/ossn_userdata");
define("AVATAR_DEFAULT", "https://i1.wp.com/grueneroadpharmacy.com/wp-content/uploads/2017/02/user-placeholder-1.jpg");
define("COVER_DEFAULT", "https://i1.wp.com/grueneroadpharmacy.com/wp-content/uploads/2017/02/user-placeholder-1.jpg");
define("DOMAIN_NAME", "amely.com");
define("SMS", "http://sms.amely.com");

return [
    'settings' => [
        'determineRouteBeforeAppMiddleware' => true,
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
        'db' => [
            "host" => "localhost",
            "dbname" => "amelyV1",
            "user" => "root",
            "pass" => "root"
        ],
        'mail' => [
            'mail_SMTPDebug' => 0,
            'mail_Host' => 'in-v3.mailjet.com',
            'mail_SMTPAuth' => true,
            'mail_Username' => '1f03b62014b346a06e7b24286172c299',
            'mail_Password' => '9d2c1b8179722b042d809e700dc5c473',
            'mail_From' => 'amelywebmaster@gmail.com',
            'mail_Sitename' => 'AMELY',
            'mail_SMTPSecure' => 'tls',
            'mail_Port' => 25
        ],
        'url' => "https://amely.com",
        'prefix' => "/ws/v1",

    ],
];
