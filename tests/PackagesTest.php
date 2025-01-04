<?php

/*
 * Client packages addon for Bear Framework
 * https://github.com/ivopetkov/client-packages-bearframework-addon
 * Copyright (c) Ivo Petkov
 * Free to use under the MIT license.
 */

/**
 * @runTestsInSeparateProcesses
 */
class PackagesTest extends BearFramework\AddonTests\PHPUnitTestCase
{

    /**
     * 
     */
    public function testPackages()
    {
        $app = $this->getApp();

        $app->clientPackages
            ->add('test1', function (IvoPetkov\BearFrameworkAddons\ClientPackage $package): void {
                $package->addJSCode('var a = 5;');
                $package->get = 'return a;';
            });

        $html = '<html><head><link rel="client-packages-embed" name="test1"></head><body>content</body></html>';
        $result = $app->clientPackages->process($html);

        $this->assertTrue(strpos($result, '<script>var clientPackages=') !== false);
        $this->assertTrue(strpos($result, 'clientPackages.__a("test1", "return a;");') !== false);
        $this->assertTrue(strpos($result, '<body>content</body>') !== false);
    }
}
