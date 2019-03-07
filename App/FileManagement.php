<?php

namespace Webp\App;

use Webp\App\AdminNotice;
use Webp\App\Helpers;
use Webp\App\PlatformFunctions\WordPress;

/**
 * @author Jack Wright
 * @version v0.0.1
 *
 * Manages the actions which can be performed using the file (deleting and attaching)
 */
class FileManagement
{
    /**
     * hideWebpFiles() - Hiding the webp files from the media library
     *
     * @param WP_Query - the query object
     * @return WP_Query - the modified query object
     */
    public function hideWebpFiles($query)
    {
        $query->set('meta_query', [
            [
                'value' => 'webp',
                'compare' => 'NOT LIKE'
            ]
        ]);

        return $query;
    }

    /**
     * deleteWebpFiles() - Delete hidden webp files when it's sibling (non-webp image) is deleted
     *
     * @param int - original file id
     */
    public function deleteWebpFiles(int $fileId)
    {
        $helper = new Helpers();

        $fileName = $helper->getWebpFileName($fileId);
        $attachmentId = $helper->getFileId($fileName);
        (new WordPress())->deleteAttachment($attachmentId);
    }

    /**
     * addBulkTerm() - Add the convert to webp functionality as a bulk action
     *
     * @param array - array of all the current bulk actions for the site
     */
    public function addBulkTerm(array $actions) : array
    {
        $actions['convert'] = 'Convert to WebP';

        return $actions;
    }

    /**
     * bulkConversion() - Perform conversion for all current images on a site
     *
     * @param string - $redirectUrl url that the page will land on post action
     * @param string - $action is the action being performed
     * @param array - $items are the items that have been selected
     * @return string - $redirectUrl
     */
    public function bulkConversion(string $redirectUrl, string $action, array $items)
    {
        if ($action !== 'convert') return;

        $alerts = $this->convertImages($items);

        (new AdminNotice())->setWebpConvertNotice($alerts);

        return $redirectUrl;
    }

    /**
     * bulkCreate() - create the necessary files when looping through
     *
     * @param array - $item is an array with details about the original file
     */
    public function bulkCreate($item)
    {
        $filePath = (new WordPress())->getAttachedFile($item);
        (new WebpConversion())->convert($filePath, true);
    }

    /**
     * convertImages() - If an image, run the bulk create method and increment the converted value by 1
     * if not an image increment unable value by 1 and then continue to next item in the array
     *
     * @param array - the items that are selected for bulk processing
     * @return array - the converted and unable amounts
     */
    public function convertImages(array $items) : array
    {
        $alerts = [
            'converted' => 0,
            'unable' => 0
        ];

        foreach ($items as $key => $item ) {
            $mimeType = (new WordPress())->getPostMimeType($item);
            if ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg' || $mimeType === 'image/png') {
                $this->bulkCreate($item, $key);
                $alerts['converted']++;
            } else {
                $alerts['unable']++;
                continue;
            }
        }

        return $alerts;
    }
}
