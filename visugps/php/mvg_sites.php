<?php
/*
Script: mvg_weather.php
        Receive positions from phone and store it in the database

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

Copyright (c) 2008 Victor Berchet, <http://www.victorb.fr>

*/

header('Content-type: text/plain; charset=ISO-8859-1');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

$lat = isset($_GET['lat'])?floatval($_GET['lat']):44;
$lon = isset($_GET['lon'])?floatval($_GET['lon']):6;

echo getSites($lat, $lon);

function getSites($lat, $lon) {
    $url = "http://www.paraglidingearth.com/takeoff_around.php?lat=$lat&lng=$lon&distance=100&limit=15";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);

    $xml = @simplexml_load_string($data);
    
    if ($xml === false) {
        return "";
    }

    for ($siteNum = 0; $siteNum < count($xml->takeoff); $siteNum++) {
        $msg .= round($xml->takeoff[$siteNum]->distance / 1000, 2) .
                "km\n" . $xml->takeoff[$siteNum]->name .
                " [" . $xml->takeoff[$siteNum]->lat . " " . $xml->takeoff[$siteNum]->lng. "]\n";
    }

    return $msg;

}

?>
