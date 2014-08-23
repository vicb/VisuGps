<?php
/*
Script: vg_proxy.php
        Download a GPS track and return it in a JSON format

License: GNU General Public License

This file is part of VisuGps

VisuGps is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

VisuGps is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with VisuGps; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Copyright (c) 2007-2008 Victor Berchet, <http://www.victorb.fr>

*/

require_once 'vg_cfg.inc.php';
require_once 'vg_tracks.php';
require_once 'vg_doarama.php';
require_once 'vg_cache.php';

use Doarama\Doarama;

if (isset($_GET['track'])) {
    $doarama = new Doarama(getenv(DOARAMA_API_NAME_VAR), getenv(DOARAMA_API_KEY_VAR));
    $cache = new Cache(CACHE_BASE_FOLDER . CACHE_FOLDER_TRACK, CACHE_NB_TRACK, 9);
    $url = $_GET['track'];

    if (isset($_GET['doaramaUpload'])) {
        // The track has been uploaded and is in cache - this request is triggered by the
        // doarama plugin to upload the fixes
        if ($cache->get($data, $url)) {
            $jsTrack = json_decode($data, true);
            // Assume the doarama upload will be fine
            $jsTrack['doaramaUpload'] = true;
            $activity = buildActivity($url);
            if (isset($jsTrack['doaramaVId'])) {
                $doarama = new Doarama(getenv(DOARAMA_API_NAME_VAR), getenv(DOARAMA_API_KEY_VAR));
                $activity->id = $jsTrack['doaramaVId'];
                // Update the cache with a success upload status (doaramaUpload = true)
                $cache->set(@json_encode($jsTrack), $url);
                if (!$doarama->uploadActivity($activity)) {
                    // Set the upload status to false on failure
                    $jsTrack['doaramaUpload'] = false;
                    $cache->set(@json_encode($jsTrack), $url);
                }
            }
        }
    } else {
        $activity = null;

        if ($cache->get($data, $url)) {
            $jsTrack = json_decode($data, true);
        } else {
            $activity = buildActivity($url);
            $jsTrack = buildJsonTrack($activity->trackData);
            $visuKey = $doarama->createVisualization($activity);
            $jsTrack['doaramaVId'] = $activity->id;
            $jsTrack['doaramaUrl'] = $doarama->getVisualizationUrl($visuKey);
            if (!isset($jsTrack['error']) && !isset($jsTrack['kmlUrl'])) {
                $cache->set(@json_encode($jsTrack), $url);
            }
        }

        echo @json_encode($jsTrack);
    }
} else if (isset($_GET['trackid'])) {
    header('Content-type: text/plain; charset=ISO-8859-1');
    header('Cache-Control: no-cache, must-revalidate');
    echo GetDatabaseTrack(intval($_GET['trackid']));
} else {
    header('Content-type: text/plain; charset=ISO-8859-1');
    header('Cache-Control: no-cache, must-revalidate');
    echo @json_encode(array('error' => 'invalid URL'));
}


