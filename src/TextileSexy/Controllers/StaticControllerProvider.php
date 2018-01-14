<?php

namespace TextileSexy\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;

class StaticControllerProvider implements ControllerProviderInterface
{
    use \TextileSexy\Services\UtilsTraits;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $controllers->match('cookies/', function (Application $app) {
            return $app['twig']->render('/static/cookies.twig', array(
                        'basket' => $app['session']->get('basket'),
                        'countItems' => $this->countItemsFromBasket($app),
                        'basketURL' => $app['session']->get('cart')['basketURL']
            ));
        });
        $controllers->match('apropos/', function (Application $app) {
            return $app['twig']->render('/static/apropos.twig', array(
                        'basket' => $app['session']->get('basket'),
                        'countItems' => countItemsFromBasket($app),
                        'basketURL' => $app['session']->get('cart')['basketURL']
            ));
        });
        return $controllers;
    }
}
