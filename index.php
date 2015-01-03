<?php
require 'vendor/autoload.php';

$app = new \Slim\Slim(array(
    'debug' => true
));

$app->get('/', function () {
    echo "<pre>\n\n\n<h2>&nbsp;&nbsp;&nbsp;To access the api go to: <a href=\"./v1\">v1</a></h2></pre>";
});

$app->run();
