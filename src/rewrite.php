<?php

/**
 *
 * @author Richard Brown <richard@agilepixel.io>
 * @copyright 2019 Agile Pixel
 *
 * @version v0.2.0
 *
 */

require_once __DIR__ . '/../../../autoload.php';
$imagesRespond = new AgilePixel\ImagesRespond\ImagesRespond();
$imagesRespond->respond($_SERVER['REQUEST_URI']);
