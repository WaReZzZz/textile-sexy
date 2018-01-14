<?php

namespace TextileSexy\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\Lookup;

class ArticleControllerProvider implements ControllerProviderInterface
{
    use \TextileSexy\Services\UtilsTraits;

    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $app->get(
            '/produit/{asin}/',
            function (\Silex\Application $app, $asin) {
                $pages = $app['pages'];
                $conf = new GenericConfiguration();
                $client = new \GuzzleHttp\Client();
                $request = new \ApaiIO\Request\GuzzleRequest($client);
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

                $lookup = new Lookup();
                $lookup->setItemId($asin);
                $lookup->setResponseGroup(array('OfferFull', 'Images', 'Variations', 'EditorialReview'));
                $response = $apaiIO->runOperation($lookup);
                //var_dump($response['Items']['Item']);
                return $app['twig']->render(
                    'item.twig',
                    array(
                        'item' => $response->Items->Item,
                        'basket' => $app['session']->get('basket'),
                        'countItems' => $this->countItemsFromBasket($app),
                    'basketURL' => $app['session']->get('cart')['basketURL'])
                );
            }
        )->bind('ensembles');

        return $controllers;
    }
}
