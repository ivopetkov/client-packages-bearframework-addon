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

$context = $app->contexts->get(__FILE__);

$context->classes
        ->add('IvoPetkov\BearFrameworkAddons\ClientPackage', 'classes/ClientPackage.php')
        ->add('IvoPetkov\BearFrameworkAddons\ClientPackages', 'classes/ClientPackages.php')
        ->add('IvoPetkov\BearFrameworkAddons\ClientPackages\Internal\Utilities', 'classes/ClientPackages/Internal/Utilities.php');

$app->shortcuts
        ->add('clientPackages', function() {
            return new IvoPetkov\BearFrameworkAddons\ClientPackages();
        });

$context->assets
        ->addDir('packages');

$app->assets
        ->addEventListener('beforePrepare', function(\BearFramework\App\Assets\BeforePrepareEventDetails $eventDetails) use ($context) {
            $matchingDir = $context->dir . '/packages/';
            if (strpos($eventDetails->filename, $matchingDir) === 0) {
                $parts = explode('/', $eventDetails->filename);
                $name = substr($parts[sizeof($parts) - 1], 0, -3);
                $eventDetails->filename = Utilities::prepareAssetResponse($name);
            }
        });

// Maybe it should not be global
$app
        ->addEventListener('beforeSendResponse', function(\BearFramework\App\BeforeSendResponseEventDetails $details) use ($app) {
            $response = $details->response;
            if ($response instanceof \BearFramework\App\Response\HTML) {
                $response->content = $app->clientPackages->process($response->content);
            }
        });
