<?php
// DIC configuration

$container = $app->getContainer();
// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

$container['db'] = function ($c) {
    $settings = $c->get('settings')['db'];
    $mysqli = new mysqli($settings['host'],$settings['user'],$settings['pass'],$settings['dbname']);
    $mysqli->set_charset("utf8");
    return $mysqli;

    // $pdo = new PDO("mysql:host=" . $settings['host'] . ";dbname=" . $settings['dbname'],
    //     $settings['user'], $settings['pass'], [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);
    // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    // return $pdo;
};
$container['prefix'] = function ($c) {
    return $c->get('settings')['prefix'];
};

$container['administrator'] = function ($c) {
    return $c->get('settings')['administrator'];
};

$container['responseURL'] = function ($c) {
    return $c->get('settings')['responseURL'];
};

$container['elasticsearch_host'] = function ($c) {
    return $c->get('settings')['elasticsearch_host'];
};

$container['mail'] = function ($c) {
    return (object)$c->get('settings')['mail'];
};



global $settings, $connectDB, $email, $elasticsearch;
$settings = $container['settings'];
$connectDB = $container['db'];
$mail = $container['mail'];

$elasticsearch = \Elasticsearch\ClientBuilder::create()           // Instantiate a new ClientBuilder
                    ->setHosts(array($container['elasticsearch_host']))     // Set the hosts
                    ->build(); 
