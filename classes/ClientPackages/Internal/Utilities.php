<?php

/*
 * Client packages addon for Bear Framework
 * https://github.com/ivopetkov/client-packages-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\ClientPackages\Internal;

use BearFramework\App;
use IvoPetkov\BearFrameworkAddons\ClientPackage;

/**
 *
 */
class Utilities
{

    const ADD_JS_CODE_VERSION = 1;

    /**
     *
     * @var array 
     */
    static $packages = [];

    /**
     *
     * @var \BearFramework\App\Contexts\Context 
     */
    static $context = null;

    /**
     * 
     * @param string $name
     * @return string|null
     */
    static function getUrl(string $name): ?string
    {
        if (isset(self::$packages[$name])) {
            if (self::$context === null) {
                $app = App::get();
                self::$context = $app->contexts->get(__FILE__);
            }
            $version = self::$packages[$name][1];
            return self::$context->assets->getURL('packages/' . $name . '.js', ['cacheMaxAge' => 999999999, 'version' => md5($version . md5(self::ADD_JS_CODE_VERSION)), 'robotsNoIndex' => true]);
        }
        return null;
    }

    /**
     * 
     * @param string $name
     * @return string
     */
    static function prepareAssetResponse(string $name): string
    {
        if (isset(self::$packages[$name])) {
            $version = self::$packages[$name][1];
            $app = App::get();
            $tempDataKey = '.temp/client-packages/' . md5($name . md5($version) . md5('THIS-FUNCTION-VERSION-' . self::ADD_JS_CODE_VERSION)) . '.js';
            if (!$app->data->exists($tempDataKey)) {
                $resources = Utilities::getResources([$name]);
                $jsFiles = array_unique(array_merge($resources['jsFiles'], $resources['jsFilesAsync']));
                $jsCode = $resources['jsCode'];
                $cssFiles = $resources['cssFiles'];
                $cssCode = $resources['cssCode'];
                if (!empty($jsFiles)) {
                    $jsFiles = array_flip($jsFiles);
                    array_walk($jsFiles, function(&$value) {
                        $value = 0;
                    });
                }
                if (!empty($cssFiles)) {
                    $cssFiles = array_flip($cssFiles);
                    array_walk($cssFiles, function(&$value) {
                        $value = 0;
                    });
                }
                //$result = file_get_contents(__DIR__ . '/../../../dev/addClientPackage.js');
                $result = include __DIR__ . '/../../../assets/addClientPackage.min.js.js';
                $result = str_replace('["PLACE_JS_FILES_HERE"]', json_encode($jsFiles), $result);
                $result = str_replace('["PLACE_JS_CODE_HERE"]', json_encode($jsCode), $result);
                $result = str_replace('["PLACE_CSS_FILES_HERE"]', json_encode($cssFiles), $result);
                $result = str_replace('["PLACE_CSS_CODE_HERE"]', json_encode($cssCode), $result);
                $app->data->setValue($tempDataKey, $result);
            }
            return $app->data->getFilename($tempDataKey);
        }
        return '';
    }

    /**
     * 
     * @param string $name
     * @return ClientPackage|null
     */
    static function getPackage(string $name): ?ClientPackage
    {
        if (isset(self::$packages[$name])) {
            $package = new ClientPackage();
            $package->name = $name;
            call_user_func(self::$packages[$name][0], $package);
            return $package;
        }
        return null;
    }

    /**
     * 
     * @param array $names
     * @return array
     */
    static function getResources(array $names): array
    {

        $jsFiles = [];
        $jsFilesAsync = [];
        $jsCode = [];
        $cssFiles = [];
        $cssCode = [];

        $addedPackages = [];

        $addPackage = function($name) use (&$addPackage, &$addedPackages, &$jsFiles, &$jsFilesAsync, &$jsCode, &$cssFiles, &$cssCode) {
            if (isset($addedPackages[$name])) {
                return;
            }
            $addedPackages[$name] = 1;
            $package = self::getPackage($name);
            if ($package !== null) {
                if (!empty($package->resources)) {
                    foreach ($package->resources as $resource) {
                        if ($resource['type'] === 'file') {
                            if ($resource['mimeType'] === 'text/javascript') {
                                if (isset($resource['async']) && (int) $resource['async'] > 0) {
                                    $jsFilesAsync[] = $resource['url'];
                                } else {
                                    $jsFiles[] = $resource['url'];
                                }
                            } elseif ($resource['mimeType'] === 'text/css') {
                                $cssFiles[] = $resource['url'];
                            }
                        } elseif ($resource['type'] === 'text') {
                            if ($resource['mimeType'] === 'text/javascript') {
                                $jsCode[] = $resource['value'];
                            } elseif ($resource['mimeType'] === 'text/css') {
                                $cssCode[] = $resource['value'];
                            }
                        } elseif ($resource['type'] === 'prepare') {
                            $_name = $resource['name'];
                            $key = 'prepare-' . $_name;
                            if (!isset($jsCode[$key])) {
                                $url = Utilities::getUrl($_name);
                                if ($url !== null) {
                                    $jsCode[$key] = 'clientPackages.__p(' . json_encode($_name) . ',' . json_encode($url) . ');';
                                }
                            }
                        } elseif ($resource['type'] === 'embed') {
                            $addPackage($resource['name']);
                        }
                    }
                }
                $init = trim($package->init);
                $jsContent = '';
                if (isset($init[0])) {
                    $jsContent .= $init;
                }
                $get = trim($package->get);
                $jsContent .= 'clientPackages.__a(' . json_encode($name) . ', ' . json_encode($get) . ');';
                $jsCode[] = $jsContent;
            }
        };

        $names = array_unique($names);
        foreach ($names as $name) {
            $addPackage($name);
        }
        return [
            'jsFiles' => array_values($jsFiles),
            'jsFilesAsync' => array_values($jsFilesAsync),
            'jsCode' => array_values($jsCode),
            'cssFiles' => array_values(array_unique($cssFiles)),
            'cssCode' => array_values(array_unique($cssCode))
        ];
    }

}
