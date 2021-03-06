<?php
/* status
 0 chua thanh toan
 1 da nhap kho
 2 that bai
 3 thanh cong
 4 tra hang
 5 dang cho giao hang


 dang xu ly
 da xu ly

*/
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
            "dbname" => "amelyv1",
            "user" => "root",
            "pass" => ""
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
        'fb' => [
            'key' => 'amely-f8329ed3e753.json'
        ],
        'nodejs' => [
            'host' => 'localhost',
            'port' => 16444
        ],
        'sms' => 'http://sms.amely.com',
        'image' => [
            'url' => 'http://userdata.local',
            'path' => 'C:\xampp\htdocs\api',
            'avatar' => 'http://userdata.amely.com/default1.png',
            'cover' => 'http://userdata.amely.com/default2.jpg'
        ],
        'root' => 'C:\xampp\htdocs\api',
        'domain' => 'amely.com',
        'url' => "http://api.local",
        'prefix' => "/ws/v1",
        'administrator' => "/ws/v1/administrator",
        'responseURL' => 'http://template.local/auth/response',
        'elasticsearch_host' => 'http://elasticsearch.amely.com:9210'
    ],
];
