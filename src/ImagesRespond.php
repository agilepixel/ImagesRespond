<?php

/**
 *
 * @author Richard Brown <richard@agilepixel.io>
 * @copyright 2018 Agile Pixel
 *
 * @version
 *
 */

namespace AgilePixel\ImagesRespond;

use Intervention\Image\ImageManagerStatic as Image;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImagesRespond
{
    public $options;
    public function __construct()
    {
        $candidates = [];

        $directory = explode(DIRECTORY_SEPARATOR, __DIR__);
        $total = count($directory);
        for ($x = 0; $x < $total; $x++) {
            $next = array_pop($directory);
            $candidates[] = implode(DIRECTORY_SEPARATOR, $directory);
        }
        $configDirectories = $candidates;

        $locator = new FileLocator($configDirectories);

        $loaderResolver = new LoaderResolver([new ConfigurationLoader($locator)]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);
        $defaultConfig = $locator->locate('.images_respond_default', null, true);
        $default = $delegatingLoader->load($defaultConfig);

        try {
            $userConfig = $locator->locate('.images_respond', null, true);
            $config = $delegatingLoader->load($userConfig);
        } catch (\Exception $e) {
            $config = [];
        }

        $resolver = new OptionsResolver();
        $resolver->setDefaults($default);

        $this->options = $resolver->resolve($config);
    }

    public function respond($request, $echo = true)
    {
        if (extension_loaded('imagick')) {
            Image::configure(['driver' => 'imagick']);
        }

        $fallback = __DIR__ . '/' . $this->options['fallback_image'];

        $match = preg_match('/(.*)respond-([0-9]+)h?-(.*\.)(jpg|gif|png|webp|jpeg).*$/i', $request, $matches);

        if (!$match) {
            if ($echo) {
                header('HTTP/1.0 404 Not Found');
                echo Image::make($fallback)->response();
            } else {
                return Image::make($fallback);
            }
            exit();
        }
        $size = $matches[2];
        $file = __DIR__ . '/../../../..'.$matches[1].$matches[3].$matches[4];

        if (!file_exists($file)) {
            $image = Image::make($fallback)->resize($size, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            if ($echo) {
                header('HTTP/1.0 404 Not Found');
                echo $image->response();
            } else {
                return $image;
            }
            exit();
        }
        try {
            $img = Image::cache(function ($image) use ($request, $file, $size) {
                if (preg_match('/(.*)respond-([0-9]+)h-(.*\.)(jpg|gif|png|webp|jpeg).*$/i', $request)) {
                    return $image->make($file)->resize(null, $size, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                } else {
                    return $image->make($file)->resize($size, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
            }, 15, true);

            if ($echo) {
                header('Cache-Control: max-age='.(60 * 60 * 24 * 7).'');

                echo $img->response();
            } else {
                return $echo;
            }
        } catch (Exception $e) {
            header('Cache-Control: max-age='.(60 * 60 * 24 * 7).'');
            $mime = mime_content_type($file);
            header('Content-type: '.$mime);
            readfile($file);
        }
    }
}
