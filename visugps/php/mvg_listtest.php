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

$link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
mysql_select_db(dbName) or die ('Database does not exist');

$tests['tests'] = array();

// List the recent tests
$query = "SELECT id, time " .
         "FROM test " .
         "WHERE time > DATE_SUB(NOW(), INTERVAL 20 MINUTE)";
$result = mysql_query($query) or die('Query error: ' . mysql_error());

if (mysql_num_rows($result)) {
    while ($row = mysql_fetch_object($result)) {
        $test['id'] = $row->id;
        $test['time'] = $row->time;
        $tests['tests'][] = $test;
    }
}

echo @json_encode($tests);
