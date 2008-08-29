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
if (!isset($_POST['id'])) exit;
$utc = isset($_POST['utc'])?1:0;
$test = isset($_POST['test'])?true:false;
$ua = $_SERVER['HTTP_USER_AGENT'];

$link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
mysql_select_db(dbName) or die ('Database does not exist');

// Is it only a connection test ?
if ($test) {
    $id = isset($_POST['id'])?intval($_POST['id']):0;
    $insert = "INSERT INTO test (id, time) VALUES ($id, NOW())";
    mysql_query($insert) or die ('Query error: ' . mysql_error());
    exit();
}

// Get the pilot id when it exists otherwise exit
$query = sprintf("SELECT id FROM pilot WHERE pseudo = '%s'", format_mysql($_POST['id']));
$result = mysql_query($query) or die('Query error: ' . mysql_error());
if (mysql_num_rows($result) == 1) {
    $pilot = mysql_fetch_object($result);
    $pilotId = $pilot->id;
} else {
    exit;
}

// Create a new flight on start
if (isset($_POST['start'])) {
    $query = "INSERT INTO flight (pilotId, start, end, utc, ua) VALUES ($pilotId, NULL, NULL, $utc, '$ua')";
    mysql_query($query) or die('Query error: ' . mysql_error());
}

// Get the current flight id
$query = "SELECT start, end, id FROM flight WHERE pilotId = '$pilotId' ORDER BY id DESC LIMIT 0, 1";
$result = mysql_query($query) or die('Query error: ' . mysql_error());
if (mysql_num_rows($result) == 1) {
    $flight = mysql_fetch_object($result);
    $flightId = $flight->id;
    $flightNeedDate = !isset($flight->start);
    if (isset($flight->end)) {
        // This is a previous flight (start has not been received for the current flight)
        // Create a new flight
        $query = "INSERT INTO flight (pilotId, start, end, utc, ua) VALUES ($pilotId, NULL, NULL, $utc, '$ua')";
        mysql_query($query) or die('Query error: ' . mysql_error());
        $flightId = mysql_insert_id();
        $flightNeedDate = true;
    } else {
        // If the last point is too old and no start has been received
        // Create a new flight
        $query = "SELECT HOUR(TIMEDIFF(NOW(), timestamp)) as delta FROM point " .
                 "WHERE flightId = $flightId ".
                 "ORDER BY time DESC LIMIT 0, 1";
        $result = mysql_query($query) or die('Query error: ' . mysql_error());
        if (mysql_num_rows($result) == 1) {
            $flight = mysql_fetch_object($result);
            if ($flight->delta > 2) {
                $query = "INSERT INTO flight (pilotId, start, end, utc, ua) VALUES ($pilotId, NULL, NULL, $utc, '$ua')";
                mysql_query($query) or die('Query error: ' . mysql_error());
                $flightId = mysql_insert_id();
                $flightNeedDate = true;
            }
        }
    }
} else {
    $query = "INSERT INTO flight (pilotId, start, end, utc) VALUES ($pilotId, NULL, NULL, $utc)";
    mysql_query($query) or die('Query error: ' . mysql_error());
    $flightId = mysql_insert_id();
    $flightNeedDate = true;
}


// Insert the points in the database
if (isset($_POST['gps'])) {
    $insert = "INSERT INTO point (flightId, latitude, longitude, elevation, time) VALUES ";
    $points = $_POST['gps'];
    for ($i = 0; $i < count($points); $i++) {
        $data = explode(";", $points[$i]);
        $date = sprintf("20%02d-%d-%d %d:%d:%d", intval($data[8])
                                               , intval($data[7])
                                               , intval($data[6])
                                               , intval($data[3])
                                               , intval($data[4])
                                               , intval($data[5]));

        $lat = floatval($data[0]);
        $lat = (intval($lat)) + ($lat - intval($lat)) * 100 / 60;
        $lon = floatval($data[1]);
        $lon = (intval($lon)) + ($lon - intval($lon)) * 100 / 60;

        $insert .= sprintf("(%d, %f, %f, %d, '%s'),", $flightId
                                                    , $lat
                                                    , $lon
                                                    , intval($data[2])
                                                    , $date);

        if ($flightNeedDate) {
            $query = "UPDATE flight SET start = '$date' WHERE id = '$flightId'";
            mysql_query($query) or die ('Query error: ' . mysql_error());
            $flightNeedDate = false;
        }
    }
    // Remove the extra "," from the query end
    $insert = rtrim ($insert, ",");
    mysql_query($insert) or die ('Query error: ' . mysql_error());
}

// Fill the flight end date on stop
if (isset($_POST['stop'])) {
    $query = "SELECT max(time) AS date FROM point WHERE flightId = '$flightId'";;
    $result = mysql_query($query) or die('Query error: ' . mysql_error());
    if (mysql_num_rows($result) == 1) {
        $end = mysql_fetch_object($result);
        $query = "UPDATE flight SET end = '$end->date' WHERE id = '$flightId'";
        mysql_query($query) or die ('Query error: ' . mysql_error());
    } else {
        exit;
    }
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
