<?php

/*
 * Client packages addon for Bear Framework
 * https://github.com/ivopetkov/client-packages-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons\ClientPackages\Internal;

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
     * @param boolean $recursive
     * @return string|null
     */
    static function getAddJsCode(string $name, bool $recursive = true): ?string
    {
        if (isset(self::$packages[$name])) {
            $resources = Utilities::getResources([$name], [], $recursive);
            $jsFiles = array_unique(array_merge($resources['jsFiles'], $resources['jsFilesAsync']));
            $jsCode = $resources['jsCode'];
            $cssFiles = $resources['cssFiles'];
            $cssCode = $resources['cssCode'];
            if (!empty($jsFiles)) {
                $jsFiles = array_flip($jsFiles);
                array_walk($jsFiles, function (&$value): void {
                    $value = 0;
                });
            }
            if (!empty($cssFiles)) {
                $cssFiles = array_flip($cssFiles);
                array_walk($cssFiles, function (&$value): void {
                    $value = 0;
                });
            }
            //$result = file_get_contents(__DIR__ . '/../../../dev/addClientPackage.js');
            $result = include __DIR__ . '/../../../assets/addClientPackage.min.js.php';
            $result = str_replace('["PLACE_JS_FILES_HERE"]', json_encode($jsFiles), $result);
            $result = str_replace('["PLACE_JS_CODE_HERE"]', json_encode($jsCode), $result);
            $result = str_replace('["PLACE_CSS_FILES_HERE"]', json_encode($cssFiles), $result);
            $result = str_replace('["PLACE_CSS_CODE_HERE"]', json_encode($cssCode), $result);
            return $result;
        }
        return null;
    }

    /**
     * 
     * @param string $name
     * @return ClientPackage|null
     */
    static function getPackage(string $name): ?ClientPackage
    {
        if (isset(self::$packages[$name])) {
            if (self::$packages[$name][1] === null) {
                $package = new ClientPackage();
                $package->name = $name;
                call_user_func(self::$packages[$name][0], $package);
                self::$packages[$name][1] = $package;
            }
            return self::$packages[$name][1];
        }
        return null;
    }

    /**
     * 
     * @param array $packagesToEmbed
     * @param array $packagesToPrepare
     * @param array $recursive
     * @return array
     */
    static function getResources(array $packagesToEmbed, array $packagesToPrepare, bool $recursive = true): array
    {
        $result = [
            'jsFiles' => [],
            'jsFilesAsync' => [],
            'jsCode' => [],
            'cssFiles' => [],
            'cssCode' => []
        ];

        $embededPackages = [];
        $packagesToPrepare = array_flip($packagesToPrepare);

        $embed = function ($name) use (&$embed, &$embededPackages, &$packagesToPrepare, &$result, &$recursive): void {
            if (isset($embededPackages[$name])) {
                return;
            }
            $embededPackages[$name] = true;
            $package = self::getPackage($name);
            if ($package !== null) {
                if (!empty($package->resources)) {
                    foreach ($package->resources as $resource) {
                        if ($resource['type'] === 'file') {
                            if ($resource['mimeType'] === 'text/javascript') {
                                if (isset($resource['async']) && (int) $resource['async'] > 0) {
                                    $result['jsFilesAsync'][] = $resource['url'];
                                } else {
                                    $result['jsFiles'][] = $resource['url'];
                                }
                            } elseif ($resource['mimeType'] === 'text/css') {
                                $result['cssFiles'][] = $resource['url'];
                            }
                        } elseif ($resource['type'] === 'text') {
                            if ($resource['mimeType'] === 'text/javascript') {
                                $result['jsCode'][] = $resource['value'];
                            } elseif ($resource['mimeType'] === 'text/css') {
                                $result['cssCode'][] = $resource['value'];
                            }
                        } elseif ($recursive && $resource['type'] === 'prepare') {
                            $packagesToPrepare[$resource['name']] = true;
                        } elseif ($recursive && $resource['type'] === 'embed') {
                            $embed($resource['name']);
                        }
                    }
                }
                $result['jsCode'][] = 'if(typeof clientPackages!=="undefined"){clientPackages.__a(' . json_encode($name) . ', ' . json_encode(trim((string)$package->get)) . ');}'; // clientPackages may be omitted
            }
        };

        $packagesToEmbed = array_unique($packagesToEmbed);
        foreach ($packagesToEmbed as $name) {
            $embed($name);
        }
        if (!empty($packagesToPrepare)) {
            foreach ($packagesToPrepare as $name => $true) {
                if (isset($embededPackages[$name])) {
                    continue;
                }
                $code = self::getAddJsCode($name, false);
                if ($code !== null) {
                    $result['jsCode'][] = 'if(typeof clientPackages!=="undefined"){clientPackages.__p(' . json_encode($name) . ',' . json_encode($code) . ');}'; // clientPackages may be omitted
                }
            }
        }

        return $result;
    }
}
