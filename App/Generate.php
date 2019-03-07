<?php

namespace Webp\App;

use Webp\App\PlatformFunctions\WordPress;

/**
 * @author Jack Wright
 * @version v0.0.1
 *
 * Generates the metadata for the newly created webp files
 */
class Generate
{
    /**
     * generateMetaData() - Modified version of the wp_generate_attachment_metadata function
     * to work solely for creating webp metadata
     *
     * @param int - id of the attachment we are creating metadata for
     * @param string - file name of the attachment
     * @return array - metadata for the newly created webp image
     */
    public function generateMetaData(int $attachmentId, string $file) : array
    {
        $mimeType = (new WordPress())->getPostMimeType($attachmentId);
        $metadata = $this->setMetadata($mimeType, $file);

    	return $metadata;
    }

    /**
     * setMetadata() - setting the metadata for the webp version of image
     *
     * @param string - mime type of the file
     * @param string - name of the file
     * @return array - metadata for the file
     */
    public function setMetadata(string $mimeType, string $file) : array
    {
        $wordpress = new WordPress();
        $metadata = [];
        $sizes = [];

        if (preg_match('!^image/!', $mimeType) && $this->displayable($file)) {
            $imageSize = getimagesize($file);
            $metadata['width'] = $imageSize[0];
            $metadata['height'] = $imageSize[1];
            $metadata['webp'] = true;
            $metadata['file'] = $wordpress->relativeUploadPath($file);

            // Make thumbnails and other intermediate sizes.
            $additionalImageSizes = $wordpress->getAdditionalImageSizes();

            // For each image size set the dimensions
            foreach ($wordpress->getIntermediateImageSizes() as $size) {
                $sizes = $this->setImageSizes($additionalImageSizes, $sizes, $size);
            }

            if (!empty($sizes)) {
                $editor = $wordpress->getImageEditor($file, array('mime_type' => 'image/webp'));
                $metadata['sizes'] = !$wordpress->isError($editor) ? $editor->multi_resize($sizes) : '';
            }
    	}

        return $metadata;
    }

    /**
     * setImageSizes() - for each size passed in through the loop set the width,
     * height and crop metadata
     *
     * @param array - additional image sizes that need to be set
     * @param array - sizes is the array that we are populating
     * @param string - the size we are setting dimensions for
     * @return array - the sizes array with the new data
     */
    public function setImageSizes(array $additionalImageSizes, array $sizes, string $size) : array
    {
        $sizes[$size] = [
            'width' => '',
            'height' => '',
            'crop' => false
        ];

        foreach ($sizes[$size] as $key => $value) {
            if (isset($additionalImageSizes[$size][$key])) {
                // For theme-added sizes
                $sizes[$size][$key] = $key !== 'crop'
                    ? intval($additionalImageSizes[$size][$key])
                    : $additionalImageSizes[$size]['crop'];
            } else {
                // For default sizes set in options
                $sizes[$size][$key] = $key !== 'crop'
                    ? (new WordPress())->getOption("{$size}_size_" . $key[0])
                    : (new WordPress())->getOption("{$size}_crop");
            }
        }

        return $sizes;
    }

    /**
     * This is a modified version of the wordpress core function `file_is_displayable_image()`
     * Needed to be modified so that it would check against webp images
     * @param string - file name of the attachment
     * @return boolean - true if image type is equal to webp
     */
    protected function displayable(string $file) : bool
    {
        $displayableImageTypes = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP, IMAGETYPE_WEBP);

        $info = @getimagesize($file);

        return empty($info) || !in_array($info[2], $displayableImageTypes) ? false : true;
    }
}
