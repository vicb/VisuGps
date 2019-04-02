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
require('vg_cfg.inc.php');

echo "<?xml version='1.0' encoding='UTF-8'?>\n" .
     "<!DOCTYPE html PUBLIC '-//WAPFORUM//DTD XHTML Mobile 1.0//EN' 'http://www.wapforum.org/DTD/xhtml-mobile10.dtd'>\n" .
     "<html xmlns='http://www.w3.org/1999/xhtml'>\n" .
     "<head>\n" .
     "    <meta http-equiv='content-type' content='text/html; charset=UTF-8'/>\n" .
     "    <title>Map</title>\n" .
     "</head>\n" .
     "<body>\n";

// Keep going only if an id has been provided
if (isset($_GET['id'])) {
    if (!isset($_GET['zoom'])) {
      $zoom = 11;
    } else {
      $zoom = $_GET['zoom'];
    }

    $link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
    mysql_select_db(dbName) or die ('Database does not exist');

    // Map size
    $w = $h = 180;

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
        // Center psotion
        $lat = isset($_GET['lat'])?floatval($_GET['lat']):$position->latitude;
        $lon = isset($_GET['lon'])?floatval($_GET['lon']):$position->longitude;
        $img = sprintf("Date: $position->time<br/>\n" .
                       "Lieu: " . getNearbyPlace($position->latitude,$position->longitude) .
                       " [" . $position->latitude . " - " . $position->longitude . "]<br/>\n" .
                       "h: " . $position->elevation . "m (" . max(0, $position->elevation - GetElevGnd($position->latitude,$position->longitude)) . "m/sol)<br/>\n" .
                       "<img src='http://maps.google.com/staticmap?zoom=%d&size=${w}x${h}&" .
                       "center=$lat,$lon&" .
                       "maptype=mobile&markers=$position->latitude,$position->longitude,smallgreen&" .
                       "key=ABQIAAAAJPvmQMZVrrV3inIwT2t4RBQf-JSUIEMNUNF63gcoYgskNGvaZRQmUvzGcFUdj4nlylxP8SK4sRKYsg'></img>\n",
                       $zoom);
        $zoomIn = $zoomOut = $up = $down = $left = $right = "<a href='http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . "?id=" . $_GET['id'];
        $zoomIn .= "&zoom=" . ($zoom + 1) .  "&lat=$lat&lon=$lon'>+</a>\n";
        $zoomOut .= "&zoom=" . ($zoom - 1) . "&lat=$lat&lon=$lon'>-</a>\n";

        $projection = new Mercator($zoom, 256);
        $latUp = $latDown = $projection->Y($lat);
        $lonLeft = $lonRight = $projection->X($lon);
        $latUp = $projection->Lat($latUp - $h / 2);
        $latDown = $projection->Lat($latDown + $h / 2);
        $lonLeft = $projection->Lon($lonLeft - $w / 2);
        $lonRight = $projection->Lon($lonRight + $w / 2);

        $up .= "&zoom=$zoom&lat=$latUp&lon=$lon'>^</a>\n";
        $down .= "&zoom=$zoom&lat=$latDown&lon=$lon'>V</a>\n";
        $left .= "&zoom=$zoom&lat=$lat&lon=$lonLeft'>&lt;</a>\n";
        $right .= "&zoom=$zoom&lat=$lat&lon=$lonRight'>&gt;</a>\n";


        echo "$img<br/>$zoomIn | $zoomOut | $up | $down | $left | $right";
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

// Coordinate functions
class Mercator {

    private $nbTiles;
    private $radius;
    private $tileSize;

    public function __construct($zoom, $tileSize) {
        $this->nbTiles = pow(2, $zoom);
        $this->tileSize = $tileSize;
        $circumference = $this->tileSize * $this->nbTiles;
        $this->radius =  $circumference / (2 * pi());
    }

    public function X($lonDeg) {
        $lonRad = deg2rad($lonDeg);
        return ($lonRad * $this->radius) + $this->tileSize * ($this->nbTiles / 2);
    }

    public function Lon($x) {
        $lonRad = ($x - $this->tileSize * ($this->nbTiles / 2)) / $this->radius;
        $lonDeg = rad2deg($lonRad);
        return $lonDeg;
    }

    public function Y($latDeg){
        $latRad = deg2rad($latDeg);
        $y = $this->radius / 2.0 * log((1.0 + sin($latRad)) / (1.0 - sin($latRad)));
        return (-1.0 * $y + $this->tileSize * ($this->nbTiles / 2));
    }

    public function Lat($y) {
        $y = -1.0 * ($y - $this->tileSize * ($this->nbTiles / 2));
        $latRad = (pi() / 2) - (2 * atan(exp(-1.0 * $y / $this->radius)));
        return rad2deg($latRad);
    }
}

echo "</body>\n" .
     "</html>";
