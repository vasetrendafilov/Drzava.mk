<?php

session_cache_limiter(false);
session_start();

require __DIR__.'/../vendor/autoload.php';

$app = new \Slim\App([
  'settings'=>[
      'displayErrorDetails' => true,
  ],
  'db'=> [
      'driver'=>'mysql',
      'host'=>'localhost',
      'database'=>'site',
      'username'=>'root',
      'password'=>'',
      'collation'=>'utf_unicode_ci',
      'prefix'=>''
  ]
]);

$container = $app->getContainer();

$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

$container['db'] = function ($container) use ($capsule){
    return $capsule;
};
$container['csrf'] = function ($c){
    return new Slim\Csrf\Guard;
};
$container['auth'] = function ($container){
    return new \App\Auth\Auth;
};
$container['flash'] = function ($container){
    return new \Slim\Flash\Messages;
};
$container['view'] = function ($container){
    $view = new \Slim\Views\Twig(__DIR__ . '/../resources/views', [
        'cache' => false,
    ]);
    $view->addExtension(new \Slim\Views\TwigExtension(
        $container->router,
        $container->request->getUri()
    ));
    $view->addExtension(new \App\Views\CsrfExtension($container['csrf']));

    $view->getEnvironment()->addGlobal('auth',[
            'check'=> $container->auth->check(),
            'user' =>$container->auth->user(),
        ]);
    $view->getEnvironment()->addGlobal('flash', $container->flash);

    return $view;
};
$container['Validator'] = function ($container){
    return new App\Validation\Validator($container);
};
$container['HomeController'] = function($container){
    return new \App\controllers\HomeController($container);
};
$container['AuthController'] = function($container){
    return new \App\controllers\Auth\AuthController($container);
};

$app->add($container->get('csrf'));

require __DIR__.'/../app/routes.php';
