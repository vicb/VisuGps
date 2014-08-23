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
    $activity = null;

    if ($cache->get($data, $url)) {
        $jsTrack = json_decode($data, true);
    } else {
        $activity = buildActivity($url);
        $jsTrack = buildJsonTrack($activity->trackData);
        if (!isset($jsTrack['error']) && !isset($jsTrack['kmlUrl'])) {
            $cache->set(@json_encode($jsTrack), $url);
        }
    }
    if (isset($jsTrack['doaramaVKey'])) {
        $visuKey = $jsTrack['doaramaVKey'];
        unset($jsTrack['doaramaVKey']);
        $jsTrack['doaramaUrl'] = $doarama->getVisualizationUrl($visuKey);
    }

    ob_end_clean();
    header("Connection: close");
    ignore_user_abort(true);
    ob_start();

    echo @json_encode($jsTrack);

    header("Content-Length: $size");
    header('Content-type: text/plain; charset=ISO-8859-1');
    header('Cache-Control: no-cache, must-revalidate');

    ob_end_flush();
    flush();

    if ($activity !== null) {
        $doarama->uploadActivity($activity);
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

