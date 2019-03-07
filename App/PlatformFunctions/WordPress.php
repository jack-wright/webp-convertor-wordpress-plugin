<?php

namespace Webp\App\PlatformFunctions;

/**
 * @author Jack Wright
 * @version v0.0.1
 *
 * Seperated WordPress functions into their own class
 */
class WordPress
{
    /**
     * addAction() - Hooks a function or a method on to a specific action.
     *
     * @param string - $action is the wordpress action to be used
     * @param callable - $function is the function to be hooked on to the action
     * @param int - $priority is the order in which the function will be called on the action
     * @param int - $args the number of arguments the action accepts
     */
    public function addAction(string $action, callable $function, int $priority = 10, int $args = 1)
    {
        return add_action($action, $function, $priority, $args);
    }

    /**
     * addFilter() - Hook a function or method to a specific filter action.
     *
     * @param string - $action is the wordpress action to be used
     * @param callable - $function is the function to be hooked on to the action
     * @param int - $priority is the order in which the function will be called on the action
     * @param int - $args the number of arguments the action accepts
     */
    public function addFilter(string $action, callable $function, int $priority = 10, int $args = 1)
    {
        return add_filter($action, $function, $priority, $args);
    }

    /**
     * getPost() - Retrieves post data given a post ID or post object.
     *
     * @param int - $attachId the post ID
     * @return WP_Post|array|null - Type corresponding to $output on success or null on failure. When $output is OBJECT, a WP_Post instance is returned.
     */
    public function getPost(int $attachId)
    {
        return get_post($attachId);
    }

    /**
     * getPostMimeType() - Retrieve the mime type of an attachment based on the ID.
     *
     * @param int - $attachId the post ID
     * @return bool|string - False on failure or returns the mime type.
     */
    public function getPostMimeType(int $attachId)
    {
        return get_post_mime_type($attachId);
    }

    /**
     * relativeUploadPath() - Return relative path to an uploaded file
     *
     * @param string - Full path to the file.
     * @return string - Relative path on success, unchanged path on failure.
     */
    public function relativeUploadPath(string $path)
    {
        return _wp_relative_upload_path($path);
    }

    /**
     * getAdditionalImageSizes() - Retrieve additional image sizes.
     *
     * @return array - Additional images size data.
     */
    public function getAdditionalImageSizes()
    {
        return wp_get_additional_image_sizes();
    }

    /**
     * getIntermediateImageSizes() - Get the available image sizes
     *
     * @return array - Returns a filtered array of image size strings
     */
    public function getIntermediateImageSizes()
    {
        return get_intermediate_image_sizes();
    }

    /**
     * getOption() - Retrieves an option value based on an option name.
     *
     * @param string - Name of option to retrieve. Expected to not be SQL-escaped.
     * @return mixed - Value set for the option
     */
    public function getOption(string $item)
    {
        return get_option($item);
    }

    /**
     * getImageEditor() - This function is the main function that you use when you want to
     * get a reference to an image, to edit a local image on the server. It returns a WP_Image_Editor
     * instance and loads a file into it. With that you can manipulate an image by calling methods on it.
     *
     * @param string - Path to file to load
     * @param array - Additional data. Accepts mime_type, methods
     * @return mixed - WP_Image_Editor object or WP_Error on failure
     */
    public function getImageEditor(string $path, array $options = [])
    {
        return wp_get_image_editor($path, $options);
    }

    /**
     * isError() - Checks whether the passed variable is a WordPress Error.
     *
     * @param mixed - Any existing variable of a known or unknown type
     * @return boolean - True, if WP_Error. False, if not WP_Error.
     */
    public function isError($item)
    {
        return is_wp_error($item);
    }

    /**
     * uploadDirectory() - Get an array containing the current upload directory’s path and url.
     *
     * @return array - the current upload directory’s path and url
     */
    public function uploadDirectory()
    {
        return wp_upload_dir();
    }

    /**
     * insertAttachment() - This function inserts an attachment into the media library.
     *
     * @param array - Array of data about the attachment that will be written into the wp_posts table of the database.
     * @param string - Location of the file on the server.
     * @param int - Parent post id
     * @return int - Returns the resulting post ID on success or 0 on failure.
     */
    public function insertAttachment(array $attachment, string $fileName, int $postID = 0)
    {
        return wp_insert_attachment($attachment, $fileName, $postID);
    }

    /**
     * getAttachmentMetadata() - Retrieve attachment meta field for attachment ID
     *
     * @param int - Attachment ID
     * @return array|boolean - Attachment meta field or false on failure.
     */
    public function getAttachmentMetadata(int $attachId)
    {
        return wp_get_attachment_metadata($attachId);
    }

    /**
     * updateAttachmentMetadata() - Update metadata for an attachment.
     *
     * @param int - Attachment ID
     * @param array - Attachment data
     * @return boolean - True on success, False on failure
     */
    public function updateAttachmentMetadata(int $attachId, array $attachmentData)
    {
        return wp_update_attachment_metadata($attachId, $attachmentData);
    }

    /**
     * deleteAttachment() - This function deletes an attachment and all of its derivatives
     *
     * @param int - Attachment ID
     * @param boolean - Whether to bypass trash and force deletion
     */
    public function deleteAttachment(int $attachId, bool $forceDelete = false)
    {
        return wp_delete_attachment($attachId, $forceDelete);
    }

    /**
     * getAttachedFile() - Retrieve attached file path based on attachment ID
     *
     * @param int - Attachment ID
     * @return string - The file path to the attached file
     */
    public function getAttachedFile(int $attachId)
    {
        return get_attached_file($attachId);
    }

    /**
     * getTheTitle() - Retrieve post title
     *
     * @param int - Attachment ID
     * @return string - The post title
     */
    public function getTheTitle(int $attachId)
    {
        return $this->sanitizeFileName(get_the_title($attachId));
    }

    /**
     * sanitizeFileName() - Sanitizes a filename replacing whitespace with dashes
     *
     * @param string - The file name
     * @return string - Sanitized file name
     */
    public function sanitizeFileName(string $fileName)
    {
        return sanitize_file_name($fileName);
    }

    /**
     * getImageUrl() - Returns a full URL for an attachment file or false on failure.
     *
     * @param int - Attachment ID
     * @return string|boolean - Returns URL to uploaded attachment or "false" on failure.
     */
    public function getImageUrl(int $attachId)
    {
        return wp_get_attachment_url($attachId);
    }

    /**
     * getAttachmentImageSrc() - Retrieve an image to represent an attachment.
     *
     * @param int - Attachment ID
     * @param string|array - Image size. Accepts any valid image size, or an array of width and height values in pixels
     * @return array|boolean - Returns an array (url, width, height, is_intermediate), or false, if no image is available.
     */
    public function getAttachmentImageSrc(int $attachId, $size)
    {
        return wp_get_attachment_image_src($attachId, $size);
    }

    /**
     * getAttachmentImageSizes() - Retrieves the value for an image attachment’s ‘sizes’ attribute.
     *
     * @param int - Attachment ID
     * @param array|string - Image size. Accepts any valid image size, or an array of width and height values in pixels
     * @return string|bool - A valid source size value for use in a 'sizes' attribute or false.
     */
    public function getAttachmentImageSizes(int $attachId, $size)
    {
        return wp_get_attachment_image_sizes($attachId, $size);
    }

    /**
     * getAttachmentImageSrcset() - Retrieves the value for an image attachment’s ‘srcset’ attribute.
     *
     * @param int - Attachment ID
     * @return string|boolean - A 'srcset' value string or false.
     */
    public function getAttachmentImageSrcset(int $attachId)
    {
        return wp_get_attachment_image_srcset($attachId);
    }

    /**
     * setTransient() - Set/update the value of a transient.
     *
     * @param string - Transient name. Expected to not be SQL-escaped
     * @param mixed - Transient value. Expected to not be SQL-escaped.
     * @param int - Time until expiration in seconds from now, or 0 for never expires.
     * @return bool - False if value was not set and true if value was set.
     */
    public function setTransient(string $transientName, $value, int $expiration = 0)
    {
        return set_transient($transientName, $value, $expiration);
    }

    /**
     * getTransient() - If the transient does not exist or does not have a value, then the return value will be false.
     *
     * @param string - Transient name we want to get
     * @return mixed - Value of transient.
     */
    public function getTransient(string $transientName)
    {
        return get_transient($transientName);
    }

    /**
     * deleteTransient() - Delete a transient. If the specified transient does not exist then no action will be taken.
     *
     * @param string - Transient name. Expected to not be SQL-escaped.
     * @return boolean - True if successful, false otherwise.
     */
    public function deleteTransient(string $transientName)
    {
        return delete_transient($transientName);
    }

    /**
     * getPostMeta() - Retrieve post meta field for a post
     *
     * @param int - Attachment ID
     * @param string - The meta key to retrieve
     * @param bool - Whether to return a single value.
     * @return mixed - Will be an array if $single is false. Will be value of meta data field if $single is true.
     */
    public function getPostMeta(int $attachId, string $metaKey, bool $single = false)
    {
        return get_post_meta($attachId, $metaKey, $single);
    }
}
