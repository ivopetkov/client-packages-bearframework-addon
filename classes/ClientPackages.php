<?php

/*
 * Client packages addon for Bear Framework
 * https://github.com/ivopetkov/client-packages-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

use IvoPetkov\HTML5DOMDocument;
use IvoPetkov\BearFrameworkAddons\ClientPackages\Internal\Utilities;
use BearFramework\App;

/**
 *
 */
class ClientPackages
{

    /**
     * Registers a new client package.
     * 
     * @param string $name The name of the client package
     * @param callable $callback A callback that will be called when the package is needed
     * @return self Returns a instance to itself.
     * @throws \Exception
     */
    public function add(string $name, callable $callback): self
    {
        if (isset(Utilities::$packages[$name])) {
            throw new \Exception('A client package named "' . $name . '" is alread added!');
        }
        Utilities::$packages[$name] = [$callback, null];
        return $this;
    }

    /**
     * Updates the Client packages related code for the HTML string provided.
     * 
     * @param string $html The HTML string to update. 
     * @return string Returns the updated HTML string.
     */
    public function process(string $html): string
    {
        if (strpos($html, 'client-packages') !== false) {
            $splitIndex = strpos($html, '</head>');
            if ($splitIndex !== false) {
                $htmlToUpdate = substr($html, 0, $splitIndex) . '</head><body></body></html>';
                $dom = new HTML5DOMDocument();
                $dom->loadHTML($htmlToUpdate, HTML5DOMDocument::ALLOW_DUPLICATE_IDS);
                $head = $dom->querySelector('head');
                if ($head !== null) {
                    $elements = $head->querySelectorAll('link[rel^="client-packages"]');
                    $libraryInsertTarget = null;
                    $packagesToEmbed = [];
                    $packagesToPrepare = [];
                    $elementsToRemove = [];
                    if ($elements->length > 0) {
                        foreach ($elements as $element) {
                            $rel = $element->getAttribute('rel');
                            if ($rel === 'client-packages') {
                                if ($libraryInsertTarget === null) {
                                    $libraryInsertTarget = $element;
                                }
                                $elementsToRemove[] = $element;
                            } elseif ($rel === 'client-packages-embed') {
                                $name = trim((string)$element->getAttribute('name'));
                                if (isset($name[0])) {
                                    if ($libraryInsertTarget === null) {
                                        $libraryInsertTarget = $element;
                                    }
                                    $packagesToEmbed[] = $name;
                                }
                                $elementsToRemove[] = $element;
                            } elseif ($rel === 'client-packages-prepare') {
                                $name = trim((string)$element->getAttribute('name'));
                                if (isset($name[0])) {
                                    if ($libraryInsertTarget === null) {
                                        $libraryInsertTarget = $element;
                                    }
                                    $packagesToPrepare[] = $name;
                                }
                                $elementsToRemove[] = $element;
                            }
                        }
                    }
                    $hasChange = false;
                    if ($libraryInsertTarget !== null) {
                        $libraryElement = $dom->createElement('script');
                        $librarySource = include __DIR__ . '/../assets/clientPackages.min.js.php';
                        //$librarySource = file_get_contents(__DIR__ . '/../dev/clientPackages.js');
                        $app = App::get();
                        $librarySource = str_replace('URL_TO_REPLACE', $app->urls->get('/-client-packages-' . md5($app->request->base)), $librarySource);
                        $libraryElement->textContent = $librarySource;
                        $libraryInsertTarget->parentNode->insertBefore($libraryElement, $libraryInsertTarget);
                        $hasChange = true;
                    }
                    if (!empty($packagesToEmbed) || !empty($packagesToPrepare)) {
                        $resources = Utilities::getResources($packagesToEmbed, $packagesToPrepare);
                        foreach ($resources['jsFiles'] as $url) {
                            $element = $dom->createElement('script');
                            $element->setAttribute('src', $url);
                            $head->appendChild($element);
                            $hasChange = true;
                        }
                        foreach ($resources['jsFilesAsync'] as $url) {
                            $element = $dom->createElement('script');
                            $element->setAttribute('src', $url);
                            $element->setAttribute('async', 'async');
                            $head->appendChild($element);
                            $hasChange = true;
                        }
                        foreach ($resources['jsCode'] as $code) {
                            $element = $dom->createElement('script');
                            $element->textContent = $code;
                            $head->appendChild($element);
                            $hasChange = true;
                        }
                        foreach ($resources['cssFiles'] as $url) {
                            $element = $dom->createElement('link');
                            $element->setAttribute('rel', 'stylesheet');
                            $element->setAttribute('type', 'text/css');
                            $element->setAttribute('href', $url);
                            $head->appendChild($element);
                            $hasChange = true;
                        }
                        foreach ($resources['cssCode'] as $code) {
                            $element = $dom->createElement('style');
                            $element->setAttribute('type', 'text/css');
                            $element->textContent = $code;
                            $head->appendChild($element);
                            $hasChange = true;
                        }
                    }
                    foreach ($elementsToRemove as $elementToRemove) {
                        $elementToRemove->parentNode->removeChild($elementToRemove);
                        $hasChange = true;
                    }
                    if ($hasChange) {
                        $resultHTML = $dom->saveHTML();
                        $html = substr($resultHTML, 0, strpos($resultHTML, '</head>')) . substr($html, $splitIndex);
                    }
                }
            }
        }
        return $html;
    }
}
