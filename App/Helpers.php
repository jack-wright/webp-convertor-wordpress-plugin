<?php

namespace Webp\App;

use \WP_Query;
use Webp\App\PlatformFunctions\WordPress;

/**
 * @author Jack Wright
 * @version v0.0.1
 *
 * generic helper class for the plugin
 */
class Helpers
{
    /**
     * Reset the query so that the webp files are available when running any methods using this method
     *
     * @param WP_Query - The query object
     * @return WP_Query - The modified query object
     */
    public function addWebpFiles($query)
    {
        $query->set('meta_query', '');

        return $query;
    }

    /**
     * Get an attachment ID given a filename.
     *
     * @param string $filename
     * @param array $queryArgs is an empty array by default
     * @return int Attachment ID on success, 0 on failure
     */
    public function getFileId(string $fileName, array $queryArgs = []) : int
    {

        (new WordPress())->addAction('pre_get_posts', array($this, 'addWebpFiles'));

        if (empty($queryArgs)) {
            $queryArgs = [
    		    'post_type'   => 'attachment',
    		    'post_status' => 'inherit',
    		    'fields'      => 'ids',
                'posts_per_page'=> -1,
    		    'meta_query'  => [
    			    [
    			        'value'   => $fileName,
    					'compare' => 'LIKE',
    					'key'     => '_wp_attachment_title',
    				]
                ]
		    ];
        }

        $query = new WP_Query($queryArgs);
        $attachmentId = $this->getAttachmentId($query, $fileName);

        return $attachmentId;
    }

    /**
     * getAttachmentId() - get the id of the file from the filename
     *
     * @param WP_Query - $query Wordpress query object
     * @param string - $filename name of the file we want the id for
     */
    public function getAttachmentId($query, $fileName)
    {
        $attachmentId = 0;

        if ($query->have_posts()) {
            foreach ( $query->posts as $postId ) {
                $attachmentId = $this->fileSearch($postId, $fileName);
                if ($attachmentId !== 0) {
                    return $attachmentId;
                }
            }
        }

        return $attachmentId;
    }

    /**
     * Check if the filename is within the array thats beeing looped through
     *
     * @param int - $postId is the id of the attachment
     * @param string - $fileName is the name of the file we are searching for
     * @return int - $postId the id of the attachment (if the files match, else 0)
     */
    public function fileSearch(int $postId, string $fileName) : int
    {
        $meta = (new WordPress())->getAttachmentMetadata($postId);
        $originalFile = basename($meta['file']);

        if ($originalFile === $fileName) {
            return $postId;
        }

        return 0;
    }

    /**
     * Get the webp version of the file
     *
     * @param int - file ID
     * @return string - file name
     */
    public function getWebpFileName(int $fileId) : string
    {
        $file = basename((new WordPress())->getAttachedFile($fileId));
        $deconstructedFileName =  explode('.', $file);
        $webpFileName = 'webp-' . $deconstructedFileName[0] . '.webp';

        return $webpFileName;
    }
}
