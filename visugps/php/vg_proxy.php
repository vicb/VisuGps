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
    $visuId = $jsTrack['doaramaVId'];
    unset($jsTrack['doaramaVId']);
    $jsTrack['doaramaUrl'] = $doarama->getVisualizationUrl($visuId);


    // Delay the upload of the GPS fixes
    // see http://stackoverflow.com/questions/138374/close-a-connection-early/14950738#14950738
    if(!ob_start("ob_gzhandler")) {
        define('NO_GZ_BUFFER', true);
        ob_start();
    }

    echo @json_encode($jsTrack);

    //Flush here before getting content length if ob_gzhandler was used.
    if(!defined('NO_GZ_BUFFER')){
        ob_end_flush();
    }

    // get the size of the output
    $size = ob_get_length();

    // send headers to tell the browser to close the connection
    header('Connection: close');
    header('Content-type: text/plain; charset=ISO-8859-1');
    header('Cache-Control: no-cache, must-revalidate');
    header('Content-Length: $size');


    // flush all output
    ob_end_flush();
    ob_flush();
    flush();

    // if you're using sessions, this prevents subsequent requests
    // from hanging while the background process executes
    if (session_id()) session_write_close();

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

