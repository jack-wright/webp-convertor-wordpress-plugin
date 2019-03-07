<?php

use Webp\App\AdminNotice;
use Webp\App\DisplayImage;
use Webp\App\FileManagement;
use Webp\App\WebpConversion;
use Webp\App\PlatformFunctions\WordPress;

$wordpress = new WordPress();

$wordpress->addAction('mime_types', 'webpToMimeTypes');
$wordpress->addAction('wp_handle_upload_prefilter', 'convert');
$wordpress->addAction('image_make_intermediate_size', 'convert');
$wordpress->addAction('delete_attachment', 'removeWebpSiblings');
$wordpress->addAction('pre_get_posts', 'hideWebpFiles');
$wordpress->addFilter('get_image_tag_class', 'getImageClasses');
$wordpress->addFilter('post_thumbnail_html', 'thumbnailDisplay', 10, 4);
$wordpress->addFilter('get_image_tag', 'imageDisplay', 10, 6);
$wordpress->addFilter('bulk_actions-upload', 'addBulkTerm');
$wordpress->addFilter('handle_bulk_actions-upload', 'bulkWebpConversion', 10, 3);
$wordpress->addAction('admin_notices', 'webpConversionNotice');
$wordpress->addFilter('get_image_tag_class', 'getImageClasses');
$wordpress->addFilter('wp_get_attachment_image_attributes','getFeaturedImageAttributes');

/**
 * getImageClasses() - get a list of the classes attached to the original image
 * and then set a transient with the classes as a value, to be used for the picture tag
 *
 * @param string - $classes is a spaced string of classes attached to the image
 * @param string - same string of classes as was passed in
 */
function getImageClasses(string $classes) : string
{
    (new WordPress())->setTransient('webp_conversion_standard_image_classes', $classes);

    return $classes;
}

/**
 * getFeaturedImageAttributes() - Get the classes for the featured image
 *
 * @param array - $attr array of attributes attached to the featured image
 * @param array - the same array that was passed in
 */
function getFeaturedImageAttributes(array $attr) : array
{
    (new WordPress())->setTransient('webp_conversion_featured_image_classes', $attr['class']);

    return $attr;
}

/**
 * webpToMimeTypes() - Adds webp mime type to wordpress's array of types
 *
 * @param array - Wordpress's array of mime types
 * @return array - Modified array of mime types
 */
function webpToMimeTypes(array $data) : array
{
    return (new WebpConversion())->webpToMimeTypes($data);
}

/**
 * convert() - when image is uploaded, duplicates and converts. When Wordpress
 * makes different image sizes for uploaded image, it also creates for our webp image
 *
 * @param string/array - filename or array of details about the file
 * @return string/array - filename or array of details about the newly created webp file
 */
function convert($data)
{
    return (new WebpConversion())->convert($data);
}

/**
 * hideWebpFiles() - Passing in query arguments to hide Webp images from the media library
 *
 * @param WP_Query - Wordpress's query object
 * @return WP_Query - Modified query object
 */
function hideWebpFiles($query)
{
    return (new FileManagement())->hideWebpFiles($query);
}

/**
 * removeWebpSiblings() - When webp images are not in media library, this enables them
 * to still be deleted
 *
 * @param int - the post id of the original (non-webp) image
 */
function removeWebpSiblings(int $postId)
{
    return (new FileManagement())->deleteWebpFiles($postId);
}

/**
 * thumbnailDisplay() - Restructuring the markup for the featured image to display WebP
 * and allow for fallback
 *
 * @param string - $html is the original img tag
 * @param int - $postId the post the featured image is for
 * @param int - $imageId the id of the original (non-webp) image
 * @param string - $size the chosen size of the image that has been inserted
 * @return string - new picture tag
 */
function thumbnailDisplay(string $html, int $postId, int $imageId, string $size) : string
{
    return (new DisplayImage())->webpThumbnail($html, $postId, $imageId, $size);
}

/**
 * imageDisplay() - Restructuring the markup for any images that are included in the page
 * or post via the tiny MCE editor
 *
 * @param string - $html is the original img tag
 * @param int - $imageId the id of the original (non-webp) image
 * @param string - $altText the alt text string
 * @param string - $title the title of the image
 * @param string - $align the alignment of the image
 * @param string - $size the chosen size of the image that has been inserted
 * @return string - new picture tag
 */
function imageDisplay(string $html, int $imageId, string $altText, string $title, string $align, string $size) : string
{
    return (new DisplayImage())->webpImage($html, $imageId, $altText, $title, $align, $size);
}

/**
 * addBulkTerm() - Add the convert to webp functionality as a bulk action
 *
 * @param array - array of all the current bulk actions for the site
 * @return array - modified array containing 'convert to webp' bulk term
 */
function addBulkTerm(array $actions) : array
{
    return (new FileManagement())->addBulkTerm($actions);
}

/**
 * bulkConversion() - Perform conversion for all current images on a site
 *
 * @param string - $redirectUrl is the url that the page will land on post action
 * @param string - $action is the action being performed
 * @param array - $items are the items that have been selected
 */
function bulkWebpConversion(string $redirectUrl, string $action, array $items)
{
    return (new FileManagement())->bulkConversion($redirectUrl, $action, $items);
}

/**
 * webpConvertNotice() - Display the success/error notice when running bulk convert action
 */
function webpConversionNotice() {
    return (new AdminNotice())->webpConvertNoticeDisplay();
}
