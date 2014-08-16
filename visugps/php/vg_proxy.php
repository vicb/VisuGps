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


header('Content-type: text/plain; charset=ISO-8859-1');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

require_once 'vg_cfg.inc.php';
require_once 'vg_tracks.php';
require_once 'vg_doarama.php';
require_once 'vg_cache.php';

use Doarama\Doarama;

if (isset($_GET['track'])) {
    $doarama = new Doarama(getenv(DOARAMA_API_NAME_VAR), getenv(DOARAMA_API_KEY_VAR));
    $cache = new Cache(CACHE_BASE_FOLDER . CACHE_FOLDER_TRACK, CACHE_NB_TRACK, 9);

    $url = $_GET['track'];

    if ($cache->get($data, $url)) {
        $jsTrack = json_decode($data, true);
    } else {
        $activity = buildActivity($url);
        $doarama->uploadActivity($activity); // todo: send later
        $jsTrack = buildJsonTrack($activity->trackData);
        if (!isset($jsTrack['error']) && !isset($jsTrack['kmlUrl'])) {
            $cache->set(@json_encode($jsTrack), $url);
        }
    }
    $visuId = $jsTrack['doaramaVId'];
    unset($jsTrack['doaramaVId']);
    $jsTrack['doaramaUrl'] = $doarama->getVisualizationUrl($visuId);
    echo @json_encode($jsTrack);
} else if (isset($_GET['trackid'])) {
    echo GetDatabaseTrack(intval($_GET['trackid']));
} else {
    echo @json_encode(array('error' => 'invalid URL'));
}

