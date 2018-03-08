# ImagesRespond

[![Build Status](https://travis-ci.org/agilepixel/ImagesRespond.svg?branch=master)](https://travis-ci.org/agilepixel/ImagesRespond)

A quick drop-in PHP library to output scaled images for responsive purposes

## Usage

You have a large image with the following url

`http://my.site/img/my_large_image.png`

Once installed and configured, you can return a scaled version of this image with a width of 200px by amending the requested url:

`http://my.site/img/respond-200-my_large_image.png`

If you need the scaling to be based on height instead of witdh use the following:

`http://my.site/img/respond-200h-my_large_image.png`

### Installation via Composer

`composer require agilepixel/imagesrespond`

### Configure for Apache

Enter into your server configuration or .htaccess, customise based on your desired configuration

    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} respond-[0-9]+h?-.*\.(jpg|gif|png|webp|jpeg)$
    RewriteRule ^(.*)$ vendor/agilepixel/imagesrespond/src/rewrite.php [L]
    
 ### Configure for Nginx
 
 Enter the following into your Nginx server configuration, customise based on your desired configuration
 
    rewrite respond-([0-9]+)h?-.*\.(jpg|gif|png|webp|jpeg)$ /vendor/agilepixel/imagesrespond/src/rewrite.php last;
