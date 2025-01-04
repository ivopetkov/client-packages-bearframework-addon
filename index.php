<?php

/*
 * Client packages addon for Bear Framework
 * https://github.com/ivopetkov/client-packages-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\ClientPackages\Internal\Utilities;

$app = App::get();

$context = $app->contexts->get(__DIR__);

$context->classes
    ->add('IvoPetkov\BearFrameworkAddons\ClientPackage', 'classes/ClientPackage.php')
    ->add('IvoPetkov\BearFrameworkAddons\ClientPackages', 'classes/ClientPackages.php')
    ->add('IvoPetkov\BearFrameworkAddons\ClientPackages\Internal\Utilities', 'classes/ClientPackages/Internal/Utilities.php');

$app->shortcuts
    ->add('clientPackages', function () {
        return new IvoPetkov\BearFrameworkAddons\ClientPackages();
    });

$context->assets
    ->addDir('packages');

$path = '/-client-packages-' . md5((string)$app->request->base);

$app->routes
    ->add('POST ' . $path, function () use ($app) {
        $name = (string) $app->request->query->getValue('n');
        $code = Utilities::getAddJsCode($name, true);
        if ($code !== null) {
            $response = new App\Response();
            $response->content = $code;
            $response->headers
                ->set($response->headers->make('Content-Type', 'text/javascript'))
                ->set($response->headers->make('X-Robots-Tag', 'noindex, nofollow'))
                ->set($response->headers->make('Cache-Control', 'private, max-age=0'));
            return $response;
        }
    });

// Maybe it should not be global
$app
    ->addEventListener('beforeSendResponse', function (\BearFramework\App\BeforeSendResponseEventDetails $details) use ($app): void {
        $response = $details->response;
        if ($response instanceof \BearFramework\App\Response\HTML) {
            $response->content = $app->clientPackages->process($response->content);
        }
    });
