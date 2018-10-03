<?php

/**
 *
 * @author Richard Brown <richard@agilepixel.io>
 * @copyright 2018 Agile Pixel
 *
 * @version v1.0.0
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

    private $using_webp = false;

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

        if ($this->options['webp'] === true && isset($_SERVER['HTTP_ACCEPT'])) {
            $accept = explode(',', $_SERVER['HTTP_ACCEPT']);
            if (in_array('image/webp', $accept)) {
                $this->using_webp = true;
            }
        }
    }

    public function respond($request, $echo = true)
    {
        if (extension_loaded('imagick')) {
            if ($this->using_webp === false || ($this->using_webp === true && \Imagick::queryFormats('WEBP'))) {
                Image::configure(['driver' => 'imagick']);
            }
        } else if ($this->using_webp === true && !function_exists('imagewebp')) {
            $this->using_webp = false;
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

        $encode = $matches[4];
        if ($this->using_webp) {
            $encode = 'webp';
        }
        if ($encode == 'jpeg') {
            $encode = 'jpg';
        }

        $size = $matches[2];
        $file = __DIR__ . '/' . $this->options['root_dir'].$matches[1].$matches[3].$matches[4];

        if (!file_exists($file)) {
            $image = Image::make($fallback)->encode($encode)->resize($size, null, function ($constraint) {
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

        if ($this->options['save_copy']) {
            $outputurl = $matches[1].'cache/'.md5_file($file).$matches[2].'.'.$encode;
            $outputfilename = __DIR__ . '/' . $this->options['root_dir'].$outputurl;
            if (file_exists($outputfilename)) {
                if ($echo) {
                    header('Location: '.$outputurl, true, 302);
                    exit;
                } else {
                    return Image::make($outputfilename);
                }
            }

            $outputpath = __DIR__ . '/' . $this->options['root_dir'].$matches[1].'cache';
            if (!is_dir($outputpath)) {
                mkdir($outputpath);
            }
        }
        try {
            if (preg_match('/(.*)respond-([0-9]+)h-(.*\.)(jpg|gif|png|webp|jpeg).*$/i', $request)) {
                $img = Image::make($file)->encode($encode)->resize(null, $size, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            } else {
                $img = Image::make($file)->encode($encode)->resize($size, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            if ($echo) {
                header('Cache-Control: max-age='.(60 * 60 * 24 * 7).'');
                if ($this->options['save_copy']) {
                    $img->save($outputfilename);
                }
                echo $img->response();
            } else {
                return $img;
            }
        } catch (Exception $e) {
            header('Cache-Control: max-age='.(60 * 60 * 24 * 7).'');
            $mime = mime_content_type($file);
            header('Content-type: '.$mime);
            readfile($file);
        }
    }
}
