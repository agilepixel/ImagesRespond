<?php

/**
 *
 * @author Richard Brown <richard@agilepixel.io>
 * @copyright 2018 Agile Pixel
 *
 * @version
 *
 */

use AgilePixel\ImagesRespond\ImagesRespond;

use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testClassInit()
    {
        $class = new ImagesRespond();
        $class->respond('/richard/gits/ImagesRespond/images/respond-500-not_found.png', false);
        $class->respond('actually_not_found.png', false);
        $this->assertTrue(true);
    }
}
