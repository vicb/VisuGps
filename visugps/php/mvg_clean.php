<?php
/*
Script: mvg_list.php
        Retrieve tracks from the and output a JSON array

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

require('mvg_db.inc.php');


set_time_limit(0);

echo "**** Cleaning flight database ****\n";

$link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
mysql_select_db(dbName) or die ('Database does not exist');

echo "** Deleting flights having no points\n";

$ids = getCurrentIds();

foreach($ids as $id) {
    $query = "SELECT * FROM point WHERE flightId='$id'";
    $result = mysql_query($query) or die('Query error: ' . mysql_error());
    if (mysql_num_rows($result) == 0) {
        echo "  Delete flight $id with 0 points\n";
        $query = "DELETE FROM flight WHERE id='$id' LIMIT 1";
        mysql_query($query) or die('Query error: ' . mysql_error());
    }
}

echo "** Deleting flights having less than 100 points\n";

$query = "SELECT f.id AS fid, COUNT(p.flightId) AS count " .
         "FROM flight AS f, point AS p " .
         "WHERE f.id = p.flightId " .
         "GROUP BY f.id " .
         "HAVING (count < 100)";

$result = mysql_query($query) or die('Query error: ' . mysql_error());

if (mysql_num_rows($result)) {
    while ($flight = mysql_fetch_object($result)) {
        echo "  Delete flight $flight->fid with $flight->count points\n";
        $query = "DELETE FROM flight WHERE id='$flight->fid' LIMIT 1";
        mysql_query($query) or die('Query error: ' . mysql_error());
    }
}

echo "** Cleaning table 'flightInfo'\n";

$validIds = getCurrentIds();

$query = "SELECT id FROM flightInfo";
$result = mysql_query($query) or die('Query error: ' . mysql_error());
if (mysql_num_rows($result)) {
    while ($flight = mysql_fetch_object($result)) {
        if (!in_array($flight->id, $validIds)) {
            echo "  Deleting invalid flight $flight->id\n";
            $query = "DELETE FROM flightInfo WHERE id='$flight->id' LIMIT 1";
            mysql_query($query) or die('Query error: ' . mysql_error());
        }
    }
}

echo "** Cleaning table 'point'\n";

$validIds = getCurrentIds();

$query = "SELECT flightId FROM point GROUP BY flightId";
$result = mysql_query($query) or die('Query error: ' . mysql_error());
if (mysql_num_rows($result)) {
    while ($flight = mysql_fetch_object($result)) {
        if (!in_array($flight->flightId, $validIds)) {
            echo "  Deleting invalid flight $flight->flightId\n";
            $query = "DELETE FROM point WHERE flightId='$flight->flightId'";
            mysql_query($query) or die('Query error: ' . mysql_error());
        }
    }
}


mysql_close($link);

function getCurrentIds() {
    $ids = array();
    $query = "SELECT id FROM flight";
    $result = mysql_query($query) or die('Query error: ' . mysql_error());
    if (mysql_num_rows($result)) {
        while ($flight = mysql_fetch_object($result)) {
            $ids[] = $flight->id;
            //$query = "DELETE FROM flightInfo WHERE id='$flight->id' LIMIT 1";
            //mysql_query($query) or die('Query error: ' . mysql_error());
        }
    }
    return $ids;
}


?>
