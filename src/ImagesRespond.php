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

class ImagesRespond
{
    public function respond()
    {
        //TODO make the fallback dynamic so it is a more appropriate drop in solution for more sites
        $fallback = '../images/not_found.png';


        if (extension_loaded('imagick')) {
            Image::configure(['driver' => 'imagick']);
        }
        $request = $_SERVER['REQUEST_URI'];
        if (isset($_COOKIE['testing'])) {
            //exit($request);
        }
        $match = preg_match('/(.*)respond-([0-9]+)h?-(.*\.)(jpg|gif|png|webp|jpeg).*$/i', $request, $matches);
        if (!$match) {
            header('HTTP/1.0 404 Not Found');
            echo Image::make($fallback)->response();
            exit();
        }
        $size = $matches[2];
        $file = '..'.$matches[1].$matches[3].$matches[4];

        if (!file_exists($file)) {
            header('HTTP/1.0 404 Not Found');
            echo Image::make($fallback)->resize($size, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->response();
            exit();
        }
        try {
            $img = Image::cache(function ($image) {
                $request = $_SERVER['REQUEST_URI'];
                $match = preg_match('/(.*)respond-([0-9]+)h?-(.*\.)(jpg|gif|png|webp|jpeg).*$/i', $request, $matches);
                $size = $matches[2];
                $file = '..'.$matches[1].$matches[3].$matches[4];
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

            header('Cache-Control: max-age='.(60 * 60 * 24 * 7).'');

            echo $img->response();
        } catch (Exception $e) {
            header('Cache-Control: max-age='.(60 * 60 * 24 * 7).'');
            $mime = mime_content_type($file);
            header('Content-type: '.$mime);
            readfile($file);
        }
    }
}
