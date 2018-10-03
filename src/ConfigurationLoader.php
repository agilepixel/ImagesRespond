<?php

/**
 *
 * @author Richard Brown <richard@agilepixel.io>
 * @copyright 2018 Agile Pixel
 *
 * @version v0.1.1
 *
 */

namespace AgilePixel\ImagesRespond;

use Symfony\Component\Config\Loader\FileLoader;

class ConfigurationLoader extends FileLoader
{
    public function load($resource, $type = null)
    {
        $configValues = json_decode(file_get_contents($resource), true);
        return $configValues;
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource);
    }
}
