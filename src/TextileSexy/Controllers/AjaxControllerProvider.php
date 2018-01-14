<?php

namespace TextileSexy\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;

class AjaxControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $app->post('/ajax/validateCookie/', function (\Silex\Application $app) {
            if ($app['session']->get('cookie') !== null) {
                return $app->json(array('cookie' => true), 201);
            } else {
                $app['session']->set('cookie', true);
                return $app->json(array('cookie' => true), 201);
            }
        });

        $app->match('/ajax/isCookieValidated/', function (\Silex\Application $app) {
            if ($app['session']->get('cookie') !== null) {
                return $app->json(array('cookie' => true), 201);
            } else {
                return $app->json(array('cookie' => false), 201);
            }
        });

        $app->match('/ajax/addNewsletter/', function (Request $request) use ($app) {
            if ($request->isMethod('POST')) {
                $post = array(
                    'email' => $request->request->get('email'),
                );
                $app['db']->insert('newsletter', $post);
                return $app->json(array('success' => true), 201);
            }

            // display the form
            return $app->json(array('success' => false), 201);
        });

        return $controllers;
    }
}
