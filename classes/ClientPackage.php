<?php

/*
 * Client packages addon for Bear Framework
 * https://github.com/ivopetkov/client-packages-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

namespace IvoPetkov\BearFrameworkAddons;

/**
 *
 */
class ClientPackage
{

    /**
     *
     * @var string 
     */
    public $name = null;

    /**
     *
     * @var array 
     */
    public $resources = [];

    /**
     *
     * @var string|null 
     */
    public $get = null;

    /**
     * 
     * @param string $url
     * @param array $options Available values: async=>true
     * @return self
     */
    public function addJSFile(string $url, array $options = []): self
    {
        $this->resources[] = [
            'type' => 'file',
            'url' => $url,
            'async' => isset($options['async']) ? $options['async'] : false,
            'mimeType' => 'text/javascript'
        ];
        return $this;
    }

    /**
     * 
     * @param string $code
     * @return self
     */
    public function addJSCode(string $code): self
    {
        $this->resources[] = [
            'type' => 'text',
            'value' => $code,
            'mimeType' => 'text/javascript'
        ];
        return $this;
    }

    /**
     * 
     * @param string $url
     * @return self
     */
    public function addCSSFile(string $url): self
    {
        $this->resources[] = [
            'type' => 'file',
            'url' => $url,
            'mimeType' => 'text/css'
        ];
        return $this;
    }

    /**
     * 
     * @param string $code
     * @return self
     */
    public function addCSSCode(string $code): self
    {
        $this->resources[] = [
            'type' => 'text',
            'value' => $code,
            'mimeType' => 'text/css'
        ];
        return $this;
    }

    /**
     * 
     * @param string $name
     * @return self
     */
    public function preparePackage(string $name): self
    {
        $this->resources[] = [
            'type' => 'prepare',
            'name' => $name
        ];
        return $this;
    }

    /**
     * 
     * @param string $name
     * @return self
     */
    public function embedPackage(string $name): self
    {
        $this->resources[] = [
            'type' => 'embed',
            'name' => $name
        ];
        return $this;
    }
}
