<?php

namespace TextileSexy\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Search;
use ApaiIO\Operations\CartCreate;
use ApaiIO\Operations\CartAdd;

class HomeControllerProvider implements ControllerProviderInterface
{
    use \TextileSexy\Services\UtilsTraits;

    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $app->match('/', function () use ($app) {
            return $app['twig']->render('home' . '.twig', array(
                        'basket' => $app['session']->get('basket'),
                        'countItems' => $this->countItemsFromBasket($app),
                        'basketURL' => $app['session']->get('cart')['basketURL']));
        })->bind('homepage');

        $app->get('/categorie/{page}/{pageCount}', function (\Silex\Application $app, $page, $pageCount) {
            $conf = new GenericConfiguration();
            $client = new \GuzzleHttp\Client();
            $request = new \ApaiIO\Request\GuzzleRequest($client);
            $pages = $app['pages'];
            if (!$app['debug']) {
                $request->setScheme('https');
            }
            try {
                $conf
                        ->setCountry('fr')
                        ->setAccessKey(getenv('AWS_API_KEY'))
                        ->setSecretKey(getenv('AWS_API_SECRET_KEY'))
                        ->setAssociateTag(getenv('AWS_ASSOCIATE_TAG'))
                        ->setResponseTransformer(new \ApaiIO\ResponseTransformer\XmlToSimpleXmlObject())
                        ->setRequest($request);
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            $apaiIO = new ApaiIO($conf);

            $search = new Search();
            $search->setCategory('Apparel');
            if (isset($pages[$page]['keywords'])) {
                $search->setKeywords($pages[$page]['keywords']);
            } elseif (!isset($pages[$page]['browseNode'])) {
                $app->abort(404, "Post $page does not exist.");
            }
            $search->setBrowseNode($pages[$page]['browseNode']);
            $search->setResponseGroup(array('Offers', 'Images', 'Variations'));
            $search->setAvailability('Available');
            $search->setMinimumPrice(700);

            if (intval($pageCount) > 10) {
                $search->parameter['VariationPage'] = variant_int(intval($pageCount) / 10);
            }
            $search->setPage($pageCount);
            $response = $apaiIO->runOperation($search);
            if (intval($pageCount) > 1) {
                return $app['twig']->render('catalogue_body' . '.twig', array(
                            'items' => $response->Items->Item,
                            'page' => $page,
                            'pageCount' => $pageCount,
                            'basket' => $app['session']->get('basket'),
                            'countItems' => $this->countItemsFromBasket($app),
                            'basketURL' => $app['session']->get('cart')['basketURL']));
            } else {
                return $app['twig']->render('catalogue' . '.twig', array(
                            'items' => $response->Items->Item,
                            'page' => $page,
                            'pageCount' => $pageCount,
                            'basket' => $app['session']->get('basket'),
                            'countItems' => $this->countItemsFromBasket($app),
                            'basketURL' => $app['session']->get('cart')['basketURL']));
            }
        })->value('pageCount', '1')->bind('categorie');

        $app->post('/createCart', function (Request $request) use ($app) {
            $conf = new GenericConfiguration();
            $client = new \GuzzleHttp\Client();
            $ClientRequest = new \ApaiIO\Request\GuzzleRequest($client);
            if (!$app['debug']) {
                $request->setScheme('https');
            }
            try {
                $conf
                        ->setCountry('fr')
                    ->setAccessKey(getenv('AWS_API_KEY'))
                    ->setSecretKey(getenv('AWS_API_SECRET_KEY'))
                    ->setAssociateTag(getenv('AWS_ASSOCIATE_TAG'))
                        ->setRequest($ClientRequest)
                        ->setResponseTransformer(new \ApaiIO\ResponseTransformer\XmlToSimpleXmlObject());
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            $apaiIO = new ApaiIO($conf);


            $post = array(
                'asin' => $request->request->get('id'),
                'quantity' => $request->request->get('quantity'),
                'image' => $request->request->get('image'),
                'price' => $request->request->get('price'),
                'name' => $request->request->get('name')
            );

            $sCartItemId = $this->addBasket($post, $app);
            $aBasket = $app['session']->get('basket');

            if ($app['session']->get('cart') !== null) {
                $response = $this->addCart($apaiIO, $app, $post, $sCartItemId);
                //$response->Cart->CartItems->CartItem
                return $app->json(array('amazonCart' => $response, 'basket' => $aBasket), 201);
            }
            $cartCreate = new CartCreate();
            $cartCreate->addItem($post['asin'], $post['quantity']);
            $response = $apaiIO->runOperation($cartCreate);
            if (isset($response->Cart->CartId)) {
                $app['session']->set('cart', array(
                    'CartId' => (string) $response->Cart->CartId,
                    'HMAC' => (string) $response->Cart->HMAC,
                    'basketURL' => (string) $response->Cart->PurchaseURL
                ));
                $app['session']->set('basket', array_merge_recursive($aBasket, array(
                    $post['asin'] => array(
                        'CartItemId' => (string) $response->Cart->CartItems->CartItem->CartItemId
                    )
                        ))
                );
                return $app->json(array('amazonCart' => $response, 'basket' => $app['session']->get('basket')), 201);
            }
            return $app->json(array('amazonCart' => $response, 'basket' => $app['session']->get('basket')), 404);
        })->bind('createCart');



        return $controllers;
    }

    private function addCart(ApaiIO $apaiIO, $app, $post, $sCartItemId = null)
    {
        $cart = $app['session']->get('cart');
        if ($post['quantity'] > 0) {
            $oldBasket = $app['session']->get('basket');
            $cartAdd = new CartAdd();
            $cartAdd->setCartId($cart['CartId']);
            $cartAdd->setHMAC($cart['HMAC']);
            $cartAdd->addItem($post['asin'], $post['quantity']);
            $formattedResponse = $apaiIO->runOperation($cartAdd);
            $app['session']->set('basket', array_merge_recursive($oldBasket, array(
                $post['asin'] => array(
                    'CartItemId' => (string) $formattedResponse->Cart->CartItems->CartItem->CartItemId
                )
                    ))
            );
            return $formattedResponse;
        } else {
            $cartRemove = new \TextileSexy\Model\CartModify();
            $cartRemove->setCartId($cart['CartId']);
            $cartRemove->setHMAC($cart['HMAC']);
            $cartRemove->removeItem($sCartItemId, intval($post['quantity']));
            $formattedResponse = $apaiIO->runOperation($cartRemove);
            return $formattedResponse;
        }
    }

    /**
     * @param type $post
     * @param type $app
     */
    private function addBasket($post, $app)
    {
        $oldBasket = ($app['session']->get('basket') !== null) ? $app['session']->get('basket') : array();
        if (array_key_exists($post['asin'], $oldBasket)) {
            if ($post['quantity'] > 0) {
                $post['quantity'] = $oldBasket[$post['asin']]['quantity'] + $post['quantity'];
            }
        }
        if ($post['quantity'] > 0) {
            $app['session']->set('basket', array_merge($oldBasket, array(
                $post['asin'] => array(
                    'name' => (string) $post['name'],
                    'image' => (string) $post['image'],
                    'quantity' => (string) $post['quantity'],
                    'price' => (string) $post['price'],
                )
                    ))
            );
        } else {
            $aBasket = $app['session']->get('basket');
            $sCartItemId = $oldBasket[$post['asin']]['CartItemId'];
            unset($aBasket[$post['asin']]);
            $app['session']->remove('basket');
            $app['session']->set('basket', $aBasket);
            return $sCartItemId;
        }
    }
}
