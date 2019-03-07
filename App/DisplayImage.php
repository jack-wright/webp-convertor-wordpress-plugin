<?php

namespace Webp\App;

use Webp\App\Helpers;
use Webp\App\PlatformFunctions\WordPress;

/**
 * @author Jack Wright
 * @version v0.0.1
 *
 * Manages the handling of the image display for any webp images
 */
class DisplayImage
{
    /**
     * webpThumbnail() - Initialise the process for when a featured image is set
     *
     * @param string - $html is the original img tag
     * @param int - $postId the post the featured image is for
     * @param int - $imageId the id of the original (non-webp) image
     * @param string - $size the chosen size of the image that has been inserted
     * @return string - new picture tag
     */
    public function webpThumbnail(string $html, int $postId, int $imageId, string $size) : string
    {
        $webpThumbnail = $this->getWebpFileInfo($imageId, $size, 'thumbnail');
        return $this->webpDisplay($webpThumbnail, $html);
    }

    /**
     * webpImage() - Initialise the process for when an image is insterted in to the page
     *
     * @param string - $html is the original img tag
     * @param int - $imageId the id of the original (non-webp) image
     * @param string - $altText the alt text string
     * @param string - $title the title of the image
     * @param string - $align the alignment of the image
     * @param string - $size the chosen size of the image that has been inserted
     * @return string - new picture tag
     */
    public function webpImage(string $html, int $imageId, string $altText, string $title, string $align, string $size) : string
    {
        $webpImage = $this->getWebpFileInfo($imageId, $size, 'standard');
        return $this->webpDisplay($webpImage, $html);
    }

    /**
     * getWebpFileInfo() - get information needed to display the correct webp image
     *
     * @param int - $imageId the id of the original (non-webp) image
     * @param string - $size the image size that we need to get
     * @return array - the necessary information about the webp image
     */
    public function getWebpFileInfo(int $imageId, string $size, string $type) : array
    {
        $wordpress = new WordPress();
        $helper = new Helpers();
        $webpFile = [];

        $webpFile['name'] = $helper->getWebpFileName($imageId);
        $webpFile['id'] = $helper->getFileId($webpFile['name']);
        $webpFile['webp'] = $this->getImageSizeUrl($webpFile['id'], $size);
        $webpFile['original'] = $this->getImageSizeUrl($imageId, $size);
        $webpFile['sizesAttribute'] = $wordpress->getAttachmentImageSizes($imageId, $size);
        $webpFile['basicInfo'] = $wordpress->getAttachmentImageSrc($imageId, $size);
        $webpFile['width'] = $webpFile['basicInfo'][1];
        $webpFile['height'] = $webpFile['basicInfo'][2];
        $webpFile['size'] = $size;
        $webpFile['classes'] = $type === 'thumbnail'
            ? $wordpress->getTransient('webp_conversion_featured_image_classes')
            : $wordpress->getTransient('webp_conversion_standard_image_classes');
        $webpFile['alt'] = $wordpress->getPostMeta($imageId, '_wp_attachment_image_alt', true);

        $wordpress->deleteTransient('webp_conversion_standard_image_classes');
        $wordpress->deleteTransient('webp_conversion_featured_image_classes');

        return $webpFile;
    }

    /**
     * webpDisplay() - Create the picture tag to use instead of standard img tag
     * this is so that browsers that don't support webp can fallback
     *
     * @param array - $webpFile is the array of info about the image
     * @param string - $html the original img tag
     * @return string - $modifiedHtml new picture tag
     */
    public function webpDisplay(array $webpFile, string $html) : string
    {
        $modifiedHtml = '<picture>';
        $modifiedHtml .= $this->createSourceTag($webpFile);
        $modifiedHtml .= $html;
        $modifiedHtml .= '</picture>';

        return $modifiedHtml;
    }

    /**
     * getImageSizeUrl() - getting the relevant path for the selected image size
     *
     * @param int - id of the main image
     * @param string - size of the image we need to get
     * @return string/null - url to the correct size image or null
     */
    public function getImageSizeUrl(int $imageId, string $size)
    {
        $imageSizeUrl = (new WordPress())->getAttachmentImageSrc($imageId, $size);

        return $imageSizeUrl[0];
    }

    /**
     * createSourceTag() - Build the source tag with the correct classes, dimensions and srcset
     * @param array - $webpFile is the information about the webp version of the image that is being displayed
     * @return string - The finished source tag
     */
    public function createSourceTag(array $webpFile) : string
    {
        $sourceTag = '';

        if (!empty($webpFile['webp'])) {
            $sourceTag .= '<source class="' . $webpFile['classes'] . '" width="' . $webpFile['width'] . '" height="' . $webpFile['height'] . '" srcset="';

            if ($webpFile['size'] !== 'thumbnail') {
                $sourceTag .= $webpFile['size'] !== 'thumbnail'
                    ? (new WordPress())->getAttachmentImageSrcset($webpFile['id']) . '" sizes="' . $webpFile['sizesAttribute']
                    : '';
            } else {
                $sourceTag .= $webpFile['size'] !== 'thumbnail'
                    ? $webpFile['webp']
                    : '';
            }

            $sourceTag .= '" type="image/webp" alt="' . $webpFile['alt'] . '"></source>';
        }

        return $sourceTag;
    }
}
