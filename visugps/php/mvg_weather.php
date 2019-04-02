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

$lat = isset($_GET['lat'])?floatval($_GET['lat']):44.0;
$lon = isset($_GET['lon'])?floatval($_GET['lon']):6.0;

echo getMetar($lat, $lon);

if (($dept = getFrenchAreaCode($lat, $lon)) > 0) {
    if (count($stationIds = getFrenchWeatherStations($dept))) {
      foreach($stationIds as $id) {
         echo getFrenchWeatherStationWeather($id);
      }
    }

    echo "\n";

    echo getFrenchWeather($dept);
}

function getMetar($lat, $lon) {
    $windName = array("N", "NNE", "NE", "ENE",
                      "E", "ESE", "SE", "SSE",
                      "S", "SSO", "SO", "OSO",
                      "O", "ONO", "NO", "NNO", "N");

    $url = "http://ws.geonames.org/findNearByWeatherJSON?lat=$lat&lng=$lon";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($data)->weatherObservation;

    $msg = "Station: $data->stationName\n" .
           "Date: $data->datetime\n" .
           "Clouds: $data->clouds\n" .
           "Wind: " . $windName[$data->windDirection / 22.5] . "($data->windDirection) " . round($data->windSpeed * 1.852, 0) ."km/h\n" .
           "Temperature: $data->temperature\n" .
           "Dew point: $data->dewPoint\n" .
           "Humidity: $data->humidity\n".
           "Conditions: $data->weatherCondition\n\n";

    return $msg;
}

function getFrenchAreaCode($lat, $lon) {
    $url = "http://ws.geonames.org/findNearbyPlaceNameJSON?style=FULL&lat=$lat&lng=$lon";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($data)->geonames[0];

    return strtoupper($data->countryCode) == 'FR'?intval($data->adminCode2):0;
}

function getFrenchWeather($dept) {
    if ($dept < 10) $dept = "0" . $dept;
    $url = "http://www.victorb.fr/script/mfin.php?dept=$dept";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = stripAccentedChar(curl_exec($ch));
    curl_close($ch);

    return "MeteoFrance:\n $data";
}

function getFrenchWeatherStations($dept) {
    if ($dept < 10) $dept = "0" . $dept;
    $url = "http://www.balisemeteo.com/wap/bd_depart.php?dept=$dept";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);

    $nb = preg_match_all("/idBalise=(\d+)/mi", $data, $matches);

    for ($i = 0; $i < $nb; $i++) {
        $stationIds[] = $matches[1][$i];
    }

    return $stationIds;
}

function getFrenchWeatherStationWeather($id) {
    if ($dept < 10) $dept = "0" . $dept;
    $url = "http://www.balisemeteo.com/wap/bd_balise.php?idBalise=$id";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);

    $weather = "";
    // Remove leading and trailing spaces
    $data = preg_replace_callback("/^[\t ]*(.*)$/mi", create_function('$matches', 'return $matches[1];'), $data);
    $data = preg_replace_callback("/^(.*?)[\t ]*$/mi", create_function('$matches', 'return $matches[1];'), $data);
    // Remove newlines
    $data = str_replace("\n", "", $data);
    // Add newlines
    $data = str_replace("<br/>", "\n", $data);
    $data = str_replace("\n\n", "\n", $data);
    // Remove markup
    $data = str_replace("<u>", "", $data);
    $data = str_replace("</u>", "", $data);
    if (preg_match("|<em>(.*?)</em>|i", $data, $match)) {
        $weather = "* $match[1] ($id):\n";
        // Get observation time
        if (preg_match("|([\d/]+ - [\d:]+)|", $data, $match)) {
            $weather .= "$match[1]\n";
        }
        // Get observations
        if (preg_match("|<p>(.*?)</p>|si", $data, $match)) {
            $weather .= $match[1];
        }
    }

    return $weather;
}

function stripAccentedChar($msg) {
    // from http://www.randomsequence.com/articles/removing-accented-utf-8-characters-with-php/
    $search = explode(",","ç,æ,œ,á,é,í,ó,ú,à,è,ì,ò,ù,ä,ë,ï,ö,ü,ÿ,â,ê,î,ô,û,å,e,i,ø,u");
    $replace = explode(",","c,ae,oe,a,e,i,o,u,a,e,i,o,u,a,e,i,o,u,y,a,e,i,o,u,a,e,i,o,u");
    return str_replace($search, $replace, $msg);
}
