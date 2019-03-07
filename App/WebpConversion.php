<?php

namespace Webp\App;

use Webp\App\Generate;
use Webp\App\Helpers;
use Webp\App\PlatformFunctions\WordPress;

/**
 * @author Jack Wright
 * @version v0.0.1
 *
 * Duplicates and converts uploaded JPGs or PNGs to Webp images
 */
class WebpConversion
{
    /**
     * Adds webp to the accepted mime types for wordpress
     * @param array - Array containing all mime types wordpress accepts
     * @return array - Array containing the new key value pair added
     */
    public function webpToMimeTypes(array $mimes) : array
    {
        $mimes['webp'] = 'image/webp';

        return $mimes;
    }

    /**
     * convert() - Kick off the conversion process for uploaded images.
     * Check to see if the data passed in to the function through the wordpress filter
     * is an array or a string. If an array, the filter being used is the
     * 'wp_handle_upload_prefilter' which is where our duplication happens. If a string,
     * then we are using the 'image_make_intermediate_size' filter, which is what we use
     * for our resized images
     *
     * @param string/array - depending on what filter we are using. **See above**
     * @param bool - whether or not the method has been called during a bulk conversion
     * @return string/array - the modified parameter after the convert function has been made.
     */
    public function convert($data, bool $bulk = false)
    {
        $fileDetails = $this->setFileDetails($data, $bulk);

        if (empty($fileDetails)) return $data;

        $uploadDirectory = is_array($fileDetails['upload_directory'])
            ? $fileDetails['upload_directory']['path']
            : $fileDetails['upload_directory'];

        $fileToCheck = $uploadDirectory . '/' . $fileDetails['name'] . '.webp';

        if ($bulk && file_exists($fileToCheck)) return $data;

        $imageData = $this->storeCreatedImage($fileDetails, $fileDetails['add_to_lib'], $bulk);

        $this->imageFromFileType($fileDetails['type'], $fileDetails['path'], $imageData['file_destination']);

        if ($fileDetails['add_to_lib']) {
            $this->insertToLibrary($imageData['file_destination'], $imageData['upload_directory'], $imageData['file_name']);
        }

        return $data;
    }

    /**
     * setFileDetails() - Set relevant file details. If $data is an array, check to see if is an image first
     *
     * @param string|array - the data returned from the action **See convert()**
     * @param bool - whether or not the method has been called during a bulk conversion
     * @return array - the file details
     */
    public function setFileDetails($data, bool $bulk) : array
    {
        $fileDetails = [];

        if (is_array($data)) {
            switch ($data['type']) {
                case 'image/jpeg':
                case 'image/jpg':
                case 'image/png':
                    $fileDetails['path'] = $data['tmp_name'];
                    $fileDetails['file'] = pathinfo($data['name']);
                    $fileDetails['upload_directory'] = (($bulk) ? $fileDetails['file']['dirname'] : (new WordPress())->uploadDirectory());
                    $fileDetails['name'] = 'webp-' . $fileDetails['file']['filename'];
                    $fileDetails['type'] = $data['type'];
                    $fileDetails['add_to_lib'] = true;
                    break;
                default:
                    return $fileDetails;
                    break;
            }
        } else {
            $fileDetails['path'] = $data;
            $fileDetails['file'] = pathinfo($fileDetails['path']);
            $fileDetails['upload_directory'] = (($bulk) ? $fileDetails['file']['dirname'] : (new WordPress())->uploadDirectory());
            $fileDetails['name'] = 'webp-' . $fileDetails['file']['filename'];
            $fileDetails['type'] = $fileDetails['file']['extension'];
            $fileDetails['add_to_lib'] = $bulk ? true : false;
        }

        return $fileDetails;
    }


    /**
     * storeCreatedImage() - setting the file name and its destination
     * @param array - The name of the file
     * @param boolean - Whether or not the image is to be added to the db
     * @return array - An array containing new file name with extension, upload directory and the file destination
     */
    protected function storeCreatedImage(array $fileDetails, bool $addToLib, bool $bulk) : array
    {
        $wordpress = new WordPress();
        $imageData = [];

        // Get the wordpress upload directory information
        $imageData['upload_directory'] = (($bulk) ? $fileDetails['upload_directory'] : $fileDetails['upload_directory']['path']);

        // Filename needs to be sanitized using Wordpress's sanitize file name method
        $imageData['file_name'] = (new WordPress())->sanitizeFileName($fileDetails['name']);

        // Check to see whether the file exists, if yes, then we need to rename the files
        if ($addToLib && file_exists($imageData['upload_directory'] . '/' . $imageData['file_name'] . '.webp')) {
            $imageData['file_name'] = $this->duplicateFileRename($imageData['upload_directory'], $imageData['file_name']);
        }

        // Add the webp extension to the file name
        $imageData['file_name'] = $imageData['file_name'] . '.webp';

        // Set the path where the file will be placed
        $imageData['file_destination'] = $imageData['upload_directory'] . '/' . $imageData['file_name'];

        return $imageData;
    }

    /**
     * imageFromFileType() - creating a new temporary image and converting that to WebP
     * @param string $type - the type of file that is being passes in (The file extension)
     * @param string $path - The absolute path to the file being passed in
     * @param string $fileDestination - Where we have defined the file to go
     */
    protected function imageFromFileType(string $type, string $path, string $fileDestination)
    {
        ob_start();

        switch ($type) {
            case 'jpg':
            case 'jpeg':
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($path);
                imagejpeg($image, NULL, 100);
                break;
            case 'png':
            case 'image/png':
                $image = imagecreatefrompng($path);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                imagepng($image, NULL, 9);
                break;
            default:
                return false;
                break;
        }

        $cont = ob_get_contents();

        // End the output buffering
        ob_end_clean();

        // Destroy temporary image
        imagedestroy($image);

        // Create a new image from string
        $content = imagecreatefromstring($cont);

        // Convert the image to webp placing it where we defined
        imagewebp($content, $fileDestination);

        // Destroy the temporary image
        imagedestroy($content);
    }

    /**
     * prepareMetaData() - Prepare an array of post data for the attachment
     *
     * @param string - uploadDirectory
     * @param string - fileName
     * @return array - array of metadata for the new file
     */
    public function prepareMetaData($uploadDirectory , $fileName) : array
    {
        return [
        	'guid'           => $uploadDirectory . '/' . $fileName,
        	'post_mime_type' => 'image/webp',
        	'post_title'     => preg_replace( '/\.[^.]+$/', '', $fileName ),
        	'post_content'   => '',
        	'post_status'    => 'inherit'
        ];
    }

    /**
     * Add file to the media library (insert new record in to wp_posts table and create $metaData
     * that will be stored in the wp_postmeta table)
     * @param string - $path - the file destination
     * @param string - $uploadDirectory - the upload directory
     * @param string - $name - the file name
     */
    protected function insertToLibrary(string $path, string $uploadDirectory , string $name)
    {
        $wordpress = new WordPress();
        // Fetch the prepared metadata for the file
        $attachment = $this->prepareMetaData($uploadDirectory , $name);
        // Get the post ID
        $postId = (isset($_POST['post_id']) ? $_POST['post_id'] : 0);
        // Insert the attachment
        $attachId = $wordpress->insertAttachment($attachment, $path, $postId);
        // Create the metadata ourselves by using a modified version of wp_generate_attachment_metadata()
        $attachmentData = (new Generate())->generateMetaData($attachId, $path);
        // Update the db with the meta data for the newly created image
        $wordpress->updateAttachmentMetadata($attachId, $attachmentData);
    }

    /**
     * duplicateFileRename() - check to see whether the file to be duplicated already exists.
     * If so then we need to append a number to the file name so that we don't overwrite existing
     * files
     * @param string - The upload Directory
     * @param string - The file name
     * @return string - The updated file name
     */
    protected function duplicateFileRename(string $uploadDir, string $fileName) : string
    {
        // initiate a counter
        $i = 1;

        // Loop through and check against the filename with the counter included
        while (file_exists($uploadDir . '/' . $fileName . '-' . $i . '.webp')) {
            $i++;
        }

        // Once the loop has ended, append the final counter figure to the file name
        $newFileName = $fileName . '-' . $i;

        return $newFileName;
    }
}
