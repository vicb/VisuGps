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

// Keep going only if an id has been provided
if (!isset($_GET['id'])) exit;
if (!isset($_GET['zoom'])) {
  $zoom = 11;
} else {
  $zoom = $_GET['zoom'];
}

$link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
mysql_select_db(dbName) or die ('Database does not exist');

$query = sprintf("SELECT latitude, longitude FROM pilot, flight, point " .
                 "WHERE pseudo = '%s' AND " .
                 "flight.pilotId = pilot.id AND " .
                 "point.flightId = flight.id " .
                 "ORDER BY point.time DESC " .
                 "LIMIT 0,1",
                 format_mysql($_GET['id']));

$result = mysql_query($query) or die('Query error: ' . mysql_error());
if (mysql_num_rows($result) == 1) {
    $position = mysql_fetch_object($result);
    $img = sprintf("<img src='http://maps.google.com/staticmap?zoom=%d&size=200x200&" .
                   "maptype=mobile&markers=$position->latitude,$position->longitude,smallgreen&" .
                   "key=ABQIAAAAJPvmQMZVrrV3inIwT2t4RBQf-JSUIEMNUNF63gcoYgskNGvaZRQmUvzGcFUdj4nlylxP8SK4sRKYsg'></img>",
                   $zoom);
    $zoomIn = $zoomOut = "<br/><a href='http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
    $zoomIn .= "?id=" . $_GET['id'] . "&zoom=" . ($zoom + 1) .  "'>zoomIn</a>";
    $zoomOut .= "?id=" . $_GET['id'] . "&zoom=" . ($zoom - 1) . "'>zoomOut</a>";
    echo $img . $zoomIn . $zoomOut;



} else {
    echo "result: " . mysql_num_rows($result);
    exit;
}

mysql_close($link);

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

?>
