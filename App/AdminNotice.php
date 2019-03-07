<?php

namespace Webp\App;

use Webp\App\PlatformFunctions\WordPress;

/**
 * @author Jack Wright
 * @version v0.0.1
 *
 * This class controls the admin notice that displays when the bulk action has been ran
 */
class AdminNotice
{
    /**
     * setWebpConvertNotice() - set transient for the amount of images that are converted
     *
     * @param int - $converted is the amount of files that were converted
     */
    public function setWebpConvertNotice(array $alerts)
    {
        $wordpress = new WordPress();

        $wordpress->setTransient('webp_conversion_total', $alerts['converted']);
        $wordpress->setTransient('webp_unable_total', $alerts['unable']);
    }

    /**
     * webpConvertNoticeDisplay() - get transient and display the success/error notice
     */
    public function webpConvertNoticeDisplay()
    {
        $wordpress = new WordPress();

        $totalConverted = $wordpress->getTransient('webp_conversion_total');
        $totalUnable = $wordpress->getTransient('webp_unable_total');

        if ($totalConverted && $totalConverted > 0) {
            $this->buildAndDelete($totalConverted, 'success', 'webp_conversion_total');
        }

        if ($totalUnable && $totalUnable > 0) {
            $this->buildAndDelete($totalUnable, 'error', 'webp_unable_total');
        }
    }

    /**
     * buildAndDelete() - Passes value to the markup and then deletes the transient
     *
     * @param int - The amount of files processed
     * @param string - The type of alert message to display
     * @param string - The name of the transient to be deleted
     */
    public function buildAndDelete(int $value, string $type, string $transientName)
    {
        $this->noticeMarkup($value, $type);
        (new WordPress())->deleteTransient($transientName);
    }

    /**
     * noticeMarkup() - the markup for the notice that will be displayed
     *
     * @param int - $totalConverted is the amount of images converted
     * @param string - $alertType is whether it has been a success or an error
     */
    protected function noticeMarkup(int $total, string $alertType)
    {
        $adminAlert = '<div id="message" class="notice notice-' . $alertType . ' is-dismissible">';
        $adminAlert .= '<p>';
        $adminAlert .= $alertType === 'success'
            ? 'Converted '. $total .' images to WebP!'
            : 'Unable to convert ' . $total . ' files as they aren\'t images';
        $adminAlert .= '</p>';
        $adminAlert .= '</div>';

        echo $adminAlert;
    }
}
