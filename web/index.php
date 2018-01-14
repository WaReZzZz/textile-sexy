<?php

require_once __DIR__ . '/../vendor/autoload.php';

$client = new Raven_Client(getenv('SENTRY_DSN'));
$error_handler = new Raven_ErrorHandler($client);
$error_handler->registerExceptionHandler();
$error_handler->registerErrorHandler();
$error_handler->registerShutdownFunction();

$app = new Silex\Application();

try {
    (new Dotenv\Dotenv(__DIR__.'/../'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

if (null !== getenv('APPLICATION_ENV') && getenv('APPLICATION_ENV') === 'development') {
    $app['debug'] = true;
} else {
    $app['debug'] = false;
}

$app->register(new Silex\Provider\SessionServiceProvider(), array(
    'session.storage.options' => array(
        'cookie_lifetime' => 14400, //4x60minutes
        'cookie_secure' => !$app['debug'],
        'cookie_httponly' => !$app['debug']
    )
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'host' => getenv('DBHOST'),
        'dbname' => getenv('DBNAME'),
        'user' => getenv('DBUSER'),
        'password' => getenv('DBPASS'),
        'charset' => 'utf8',
    ),
));

$app->register(new Silex\Provider\SwiftmailerServiceProvider(), array(
    'swiftmailer.options' => array(
        'host' => getenv('MAIL_HOST'),
        'port' => getenv('MAIL_PORT'),
        'username' => getenv('MAIL_USERNAME'),
        'password' => getenv('MAIL_PASSWORD'),
        'encryption' => getenv('MAIL_ENCRYPTION'),
        'auth_mode' => null,
    )
));
$app['mailer'] = new \Swift_MailTransport;

//$app['autoloader']->registerNamespace('TextileSexy\Controller', __DIR__.'/../lib');
//$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../src/TextileSexy/Views',
    'twig.options' => array(
        'cache' => (!$app['debug']) ? __DIR__ . '/../cache/twig' : false,
    )
));
$app['pages'] = include_once __DIR__ . '/../src/TextileSexy/Config/pages.global.php';
$app['twig'] = $app->extend("twig", function (\Twig_Environment $twig, Silex\Application $app) {
    $twig->addExtension(new Twig_Extensions_Extension_Text());

    return $twig;
});

$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => __DIR__ . '/../cache/',
));

$app->error(function (\Exception $e, Symfony\Component\HttpFoundation\Request $request, $code) use ($app) {
    $message = \Swift_Message::newInstance('Error on Textile.sexy', $request . 'Exception : ' . $e)
        ->setFrom('contact@textile.sexy', 'Textile Sexy')
        ->setTo(array(getenv('MAIL_RECEPIENT')));
    $app['mailer']->send($message);
    return 'Error on this page the Webmaster has been warned';
});
$app->mount('/static', new \TextileSexy\Controllers\StaticControllerProvider());
$app->mount('/produit', new \TextileSexy\Controllers\ArticleControllerProvider());
$app->mount('/', new \TextileSexy\Controllers\HomeControllerProvider());
$app->mount('/ajax', new \TextileSexy\Controllers\AjaxControllerProvider());

$app->run();