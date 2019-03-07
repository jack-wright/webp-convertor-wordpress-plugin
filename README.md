# Wordpress webp converter plugin

A plugin for creating a duplicate of a PNG or JPEG on your Wordpress site, and converting it to webp.

The plugin works as soon as it is activated, there are no settings that need to be set or changed. As soon as you upload an image, the plugin duplicates and converts that image to a webp file format. All image sizes ('thumbnail' through to 'full size') are created and are kept within the same directory as the original files. This will only work for images uploaded after the plugin has been installed and activated.

The new webp images do not show in the media library, this is to ensure that webp images are not inserted in to posts by mistake. The image that is inserted or attached must be the original, so that if the browser fails to support webp format, it can fallback and use it.

On the front-end, the `<img>` tags, that Wordpress uses by default, are replaced by `<picture>` tags. Picture tags allow for fallback images, which is very important when we are using an image format that is only supported in Chrome and a small number of mobile browsers (*__14/03/2018__*).

As of

## Server set up

PHP version __>=__ 7.1, and have webP support enabled for both GD and ImageMagick image editors.

GD image editor can duplicate and create the webp image, but cannot read metadata so it cannot resize the images which is needed to work with WordPress, so that is why ImageMagick is needed.

### Steps needed to upgrade scotchbox 3.0's (Ubuntu 14.04) PHP version

The scotchbox version we are currently using is 3.0 and that comes pre-installed with 7.0.*

As stated you will need php >= 7.1 so that certain PHP functions recognise WebP format.

#### This installs php7.1
* `sudo add-apt-repository ppa:ondrej/php`
* `sudo apt-get update`
* `sudo apt-get install -y php7.1 php7.1-xml php7.1-mysql php7.1-curl php7.1-mbstring php7.1-gd`

#### This enables Apache to run php7.1
* `sudo a2dismod php7.0`
* `sudo a2enmod php7.1`
* `sudo service apache2 restart`

##### *WebP needs to be enabled for GD image editor to be able to work*

## Adding metadata to wordpress for the newly created images

If we want to create meta data for the image, so that we can display thumbnails of the image, attach image to posts etc, then we need to rebuild the imagick package so that it supports WebP.

Out the box, any linux servers don't come with webp enabled for imagick, so this needs to be sorted out. Below I have outlined the way which I managed to get around this, but I am no dev ops expert by any stretch and have just coblled togeteher bits of info I found across the net.

Thanks to this article on converting png/jpegs to webp using different image editors:
https://github.com/rosell-dk/webp-convert

### Compile libwepb from source
https://developers.google.com/speed/webp/docs/compiling

* Install the libjpeg, libpng, libtiff and libgif packages, needed to convert between JPEG, PNG, TIFF, GIF and WebP image formats.
Package management varies by Linux distribution. On Ubuntu and Debian, the following command will install the needed packages:
* `sudo apt-get install libjpeg-dev libpng-dev libtiff-dev libgif-dev`

* Download libwebp-0.6.1.tar.gz from the downloads list: https://storage.googleapis.com/downloads.webmproject.org/releases/webp/index.html (use wget command for ease)
* Untar or unzip the package. This creates a directory named libwebp-0.6.1/:
* `tar xvzf libwebp-0.6.1.tar.gz`

#### Build WebP encoder cwebp and decoder dwebp:

Go to the directory where libwebp-0.6.1/ was extracted to and run the following commands:

* `cd libwebp-0.6.1`
* `./configure`
* `make`
* `sudo make install`
* `cd ..`

This builds and installs the cwebp and dwebp command line tools, along with the libwebp libraries (dynamic and static).

### Compile imagemagick from source.
https://www.imagemagick.org/script/install-source.php

Download ImageMagick.tar.gz from https://www.imagemagick.org/download/ImageMagick.tar.gz (use `wget` command for ease)

* Unpack the distribution with this command:
* `tar xvzf ImageMagick.tar.gz`

Next configure and compile ImageMagick.
* `cd ImageMagick-7.0.7` (or whatever version you have downloaded)
* `./configure --with-webp=yes`
* `make`

If ImageMagick configured and compiled without complaint, you are ready to install it on your system. Administrator privileges are required to install. To install, type

* `sudo make install`

You may need to configure the dynamic linker run-time bindings:

* `sudo ldconfig /usr/local/lib`


### Compile php-imagick from source

Imagick is a thin api wrapper over the top of imagemagick.

#### Installing ImageMagick PHP Module
https://www.enovate.co.uk/blog/2015/12/16/how-to-install-imagemagick-from-source-on-ubuntu-14.04

Unfortunately, when installing the ImageMagick PHP Module via "apt-get install php7.1-imagick" this seems to install a seperate "imagick-common" package, which still uses the 6.7.7.10 version rather than the newer version we've just installed.

Therefore, you will need to install the PHP module manually and the process differs slightly depending on the version of PHP you're running. I am documenting how to install for php 7.1, for details on other PHP versions check out the link above.

#### Manually installing the ImageMagick PHP Module
Please note: In order to run "phpize" you may need to first install "php7.1-dev" so run "apt-get install php7.1-dev" if the command does not work.

First, we need to identify the latest version of the module from the PECL repository that support PHP7, then update the steps below with that version number (at the time of writing, 3.4.3):

* `wget http://pecl.php.net/get/imagick-3.4.3.tgz`
* `tar -xvzf imagick-3.4.3.tgz`
* `cd imagick-3.4.3/`
* `phpize`
* `./configure`
* `sudo make`
* `sudo make install`

Now restart apache `sudo service apache2 restart` and then check the imagick information using either echoing out `phpinfo()` to the browser, or running `php -i` on the command line.

You should now see webp in the supported formats, and also the version of imagemagick should be the version we compiled earlier. If this is the case, then the plugin will work as it should.
