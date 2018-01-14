<?php

namespace config;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$pages = array(
    '/' => 'login/indexAction',
    '/home' => 'add/assetAction',
    '/social' => 'search/assetAction'
);
 /*
foreach ($pages as $route => $controllerAction) {
    $aControllerAction = explode('/', $controllerAction);
    $controller = $aControllerAction[0];
    $controllerMethod = $controller.'()';
    $action = $aControllerAction[1];
    $view = str_replace('Action', '', $aControllerAction[1]);
    $app->get($route, function () use ($app, $view) {
        return $app['twig']->render($view . '.twig');
    })->bind($view);
}*/
