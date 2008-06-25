<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <title>Carte</title>
</head>
<body>
<?php
/*
Script: mvg_track.php
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
require('mvg_db.inc.php');
require('vg_cfg.php');

// Keep going only if an id has been provided
if (isset($_GET['id'])) {
    if (!isset($_GET['zoom'])) {
      $zoom = 11;
    } else {
      $zoom = $_GET['zoom'];
    }

    $link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
    mysql_select_db(dbName) or die ('Database does not exist');

    $query = sprintf("SELECT latitude, longitude, elevation, time FROM pilot, flight, point " .
                     "WHERE pseudo = '%s' AND " .
                     "pilotId = pilot.id AND " .
                     "flightId = flight.id " .
                     "ORDER BY point.time DESC " .
                     "LIMIT 0,1",
                     format_mysql($_GET['id']));

    $result = mysql_query($query) or die('Query error: ' . mysql_error());
    if (mysql_num_rows($result) == 1) {
        $position = mysql_fetch_object($result);
        $img = sprintf("Date: $position->time<br/>\n" .
                       "Lieu: " . getNearbyPlace($position->latitude,$position->longitude) . "<br/>\n" .
                       "h: " . $position->elevation . "m (" . max(0, $position->elevation - GetElevGnd($position->latitude,$position->longitude)) . "m/sol)<br/>\n" .
                       "<img src='http://maps.google.com/staticmap?zoom=%d&size=180x180&" .
                       "maptype=mobile&markers=$position->latitude,$position->longitude,smallgreen&" .
                       "key=ABQIAAAAJPvmQMZVrrV3inIwT2t4RBQf-JSUIEMNUNF63gcoYgskNGvaZRQmUvzGcFUdj4nlylxP8SK4sRKYsg'></img>\n",
                       $zoom);
        $zoomIn = $zoomOut = "<br/><a href='http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        $zoomIn .= "?id=" . $_GET['id'] . "&zoom=" . ($zoom + 1) .  "'>zoomIn</a>\n";
        $zoomOut .= "?id=" . $_GET['id'] . "&zoom=" . ($zoom - 1) . "'>zoomOut</a>\n";
        echo $img . $zoomIn . $zoomOut;
    } else {
        echo "No map available";
    }

    mysql_close($link);
} else {
?>
    <form action="<?php echo $_SERVER['PHP_SELF']?>" method"GET">
        id:
        <input type="text" name="id">
    </form>
<?php
}

function format_mysql($text) {
    if(get_magic_quotes_gpc()) {
        if(ini_get('magic_quotes_sybase')) {
            $text = str_replace("''", "'", $text);
        } else {
            $text = stripslashes($text);
        }
    }
    return mysql_real_escape_string($text);
}

function getNearbyPlace($lat, $lon) {
    $url = "http://ws.geonames.org/findNearbyPlaceNameJSON?lat=$lat&lng=$lon";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);
    return json_decode($data)->geonames[0]->name;
}

/*
Function: GetElevGnd
        Return the ground elevation
*/
function GetElevGnd($lat, $lon)
{
    $fileLat = (int)floor($lat / SRTM_TILE_SIZE_DEG) * SRTM_TILE_SIZE_DEG;
    $fileLon = (int)floor($lon / SRTM_TILE_SIZE_DEG) * SRTM_TILE_SIZE_DEG;
    $fileName = "strm3_" . $fileLat . "_" . $fileLon . ".strmb";
    $latPx = (int)floor(($fileLat + SRTM_TILE_SIZE_DEG - $lat) * (SRTM_TILE_SIZE_PX - 1) / SRTM_TILE_SIZE_DEG);
    $lonPx = (int)floor(($lon - $fileLon) * (SRTM_TILE_SIZE_PX - 1) / SRTM_TILE_SIZE_DEG);

    $elevGnd = 0;
    $handle = @fopen(SRTM_PATH . $fileName, "rb");
    if ($handle) {
        fseek($handle, $latPx * SRTM_TILE_SIZE_PX);
        $line = fread($handle, SRTM_TILE_SIZE_PX);
        $elevGnd = ord($line[$lonPx]) * 20;
    }
    fclose($handle);

    return $elevGnd;
}


?>
</body>
</html>
