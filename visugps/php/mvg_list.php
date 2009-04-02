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

$geoServerStatus = true;

$link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
mysql_select_db(dbName) or die ('Database does not exist');

$start = isset($_POST['start'])?intval($_POST['start']):0;
$limit = isset($_POST['limit'])?intval($_POST['limit']):10;
if ($start < 0) $start = 0;
if ($end < $start) $end = $start;
$sort = isset($_POST['sort'])?format_mysql($_POST['sort']):'start';
$dir = isset($_POST['dir'])?(strtoupper($_POST['dir']) == 'ASC'?'ASC':'DESC'):'DESC';
$cb = isset($_REQUEST['callback'])?preg_replace('/[^\w]/i', '-', $_REQUEST['callback']):NULL;

$filterWhere = getFilterCondition($_POST['filter']);

// Set the end time for old flights which haven't received a stop
$query = "SELECT id, end " .
         "FROM flight " .
         "WHERE end IS NULL ";
$result = mysql_query($query) or die('Query error: ' . mysql_error());
if (mysql_num_rows($result)) {
    while ($flight = mysql_fetch_object($result)) {
        $query = "SELECT time, DATEDIFF(CURDATE(), time) AS delta " .
                 "FROM point WHERE flightId=$flight->id " .
                 "HAVING delta > 1 " .
                 "ORDER BY time DESC limit 0,1";
        $result2 = mysql_query($query) or die('Query error: ' . mysql_error());
        if (mysql_num_rows($result2) == 1) {
            $point = mysql_fetch_object($result2);
            $query = "UPDATE flight SET end='$point->time' WHERE id = $flight->id";
            mysql_query($query) or die('Query error: ' . mysql_error());
        }
    }
}

// Get the total number of flights
$query = "SELECT name, start, end, utc, flightId, COUNT(latitude) as points " .
         "FROM pilot, flight, point " .
         "WHERE flightId = flight.id AND pilotId = pilot.id  " .
         $filterWhere .
         "GROUP BY flightId ".
         "HAVING points > 5 ";
$result = mysql_query($query)  or die('Query error: ' . mysql_error());
$tracks['count'] = mysql_num_rows($result);

// List flights having more than 5 points
$query = "SELECT name, start, end, utc, flightId, COUNT(latitude) as points " .
         "FROM pilot, flight, point " .
         "WHERE flightId = flight.id AND pilotId = pilot.id " .
         $filterWhere .
         "GROUP BY flightId " .
         "HAVING points > 5 " .
         "ORDER BY $sort $dir LIMIT $start, $limit";
$result = mysql_query($query)  or die('Query error: ' . mysql_error());

$tracks['tracks'] = array();

if (mysql_num_rows($result)) {
    while ($row = mysql_fetch_object($result)) {
        $track['name'] = $row->name;
        $track['flightId'] = $row->flightId;
        $track['start']['time'] = $row->start;
        $track['end']['time'] = $row->end;
        $track['live'] = ($row->end == NULL);

        // Get take-off information
        $query = "SELECT latitude, longitude FROM point " .
                 "WHERE flightId = $row->flightId " .
                 "ORDER BY point.time ASC " .
                 "LIMIT 0,1";

        if ($result2 = mysql_query($query)) {
            if (mysql_num_rows($result2) == 1) {
                $takeoff = mysql_fetch_object($result2);
                $track['start']['lat'] = $takeoff->latitude;
                $track['start']['lon'] = $takeoff->longitude;
            }
        }

        // Get landing info
        $query = "SELECT latitude, longitude FROM point " .
                 "WHERE flightId = $row->flightId " .
                 "ORDER BY point.time DESC " .
                 "LIMIT 0,1";

        if ($result2 = mysql_query($query)) {
            if (mysql_num_rows($result2) == 1) {
                $landing = mysql_fetch_object($result2);
                $track['end']['lat'] = $landing->latitude;
                $track['end']['lon'] = $landing->longitude;
            }
        }

      $flightInfo = getFlightInfo($track, $geoServerStatus);

      if ($row->utc) {
          // Convert UTC to local time
          try {
              $timeZone = new DateTimeZone($flightInfo->timezone);
              $timeOffset = timezone_offset_get($timeZone, new DateTime($track['start']['time']));
          } catch (Exception $e) {
              $timeOffset = 0;
          }
          $startTime = mysql2timestamp($track['start']['time']) + $timeOffset;
          $track['start']['time'] = date("Y-m-d H:i:s", $startTime);
          if (!$track['live']) {
              // Live tracks don't have an end time
              $endTime = mysql2timestamp($track['end']['time']) + $timeOffset;
              $track['end']['time'] = date("Y-m-d H:i:s", $endTime);
          }
      }

    $tracks['tracks'][] = $track;
    }
}

if (isset($cb)) {
  echo "$cb(" . @json_encode($tracks) . ")";
} else {
  echo @json_encode($tracks);
}

function getFilterCondition($filter) {
    if (!is_array($filter)) return "";

    $where = "";

    for ($i = 0; $i < count($filter); $i++){
        $field = $filter[$i]['field'];
        if ($field == 'name' ||
            $field == 'start') {
            switch($filter[$i]['data']['type']){
                case 'string' :
                    $where .= " AND $field LIKE '%".$filter[$i]['data']['value']."%'";
                    break;
                case 'date' :
                    $day = date('Y-m-d', strtotime($filter[$i]['data']['value']));
                    switch ($filter[$i]['data']['comparison']) {
                        case 'eq' :                            
                            $where .= " AND $field > '$day 00:00:00' AND $field < '$day 23:59:59'";
                            break;
                        case 'lt' :
                            $where .= " AND $field < '$day'";
                            break;
                        case 'gt' :
                            $where .= " AND $field > '$day'";
                            break;
                    }
                break;
            }
        }
    }
    return $where . " ";
}

function getFlightInfo(&$track, &$geoServerStatus) {
    $id = $track['flightId'];
    
    $query = "SELECT * FROM flightInfo WHERE id='$id'";
    $result = mysql_query($query)  or die('Query error: ' . mysql_error());
    if (mysql_num_rows($result) == 1) {
        $flightInfo = mysql_fetch_object($result);
    } else {
        $query = "INSERT INTO flightInfo (id) VALUES ('$id')";
        $result = mysql_query($query)  or die('Query error: ' . mysql_error());
        $query = "SELECT * FROM flightInfo WHERE id='$id'";
        $result = mysql_query($query)  or die('Query error: ' . mysql_error());
        $flightInfo = mysql_fetch_object($result);
    }
    
    // Retrieve take-off information
    if ($flightInfo->startLocation == NULL) {
        $track['start']['location'] = getNearbyPlace($track['start']['lat'], $track['start']['lon'], $geoServerStatus);
        if ($track['start']['location']['place'] != '-') {
            $query = "UPDATE flightInfo " .
                     "SET startLocation = '" . format_mysql($track['start']['location']['place']) . "', ".
                     "startCountry = '" . format_mysql($track['start']['location']['country']) . "' " .
                     "WHERE id='$id'";
            $result = mysql_query($query) or die('Query error: ' . mysql_error());
        }
    } else {
        $track['start']['location']['place'] = $flightInfo->startLocation;
        $track['start']['location']['country'] = $flightInfo->startCountry;
    }
    
    // Retrieve position / landing information
    if ($flightInfo->endLocation == NULL || $track['live']) {
        $track['end']['location'] = getNearbyPlace($track['end']['lat'], $track['end']['lon'], $geoServerStatus);
        if ($track['end']['location']['place'] != '-' && !$track['live']) {
            $query = "UPDATE flightInfo " .
                     "SET endLocation = '" . format_mysql($track['end']['location']['place']) . "', ".
                     "endCountry = '" . format_mysql($track['end']['location']['country']) . "' " .
                     "WHERE id='$id'";
            $result = mysql_query($query) or die('Query error: ' . mysql_error());
        }
    } else {
        $track['end']['location']['place'] = $flightInfo->endLocation;
        $track['end']['location']['country'] = $flightInfo->endCountry;
    }

    // Retrieve timezone information
    if ($flightInfo->timezone == NULL) {
        $flightInfo->timezone = getTimeZone($track['start']['lat'], $track['start']['lon'], $geoServerStatus);
        if ($flightInfo->timezone != NULL) {
            $query = "UPDATE flightInfo " .
                     "SET timezone = '$flightInfo->timezone' ".
                     "WHERE id='$id'";
            $result = mysql_query($query) or die('Query error: ' . mysql_error());
        }
    }

    return $flightInfo;

}

function getNearbyPlace($lat, $lon, &$status) {
    $location['place'] = '-';
    $location['country'] = '-';
    if (!$status) return $location;
    $url = "http://ws.geonames.org/findNearbyPlaceNameJSON?lat=$lat&lng=$lon";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $data = curl_exec($ch);
    $status = (curl_errno($ch) == 0);
    curl_close($ch);
    if ($status) {
        $data = json_decode($data)->geonames[0];
        $location['place'] = $data->name;
        $location['country'] = strtolower($data->countryCode);
    }
    return $location;
}

function getTimeZone($lat, $lon, &$status) {
    if (!$status) return NULL;
    $url = "http://ws.geonames.org/timezoneJSON?lat=$lat&lng=$lon";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $data = curl_exec($ch);
    $status = (curl_errno($ch) == 0);
    curl_close($ch);
    if ($status) {
        return json_decode($data)->timezoneId;
    } else {
        return NULL;
    }
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

function mysql2timestamp($datetime){
       $val = explode(" ",$datetime);
       $date = explode("-",$val[0]);
       $time = explode(":",$val[1]);
       return @mktime(intval($time[0]), intval($time[1]), intval($time[2]),
                      intval($date[1]), intval($date[2]), intval($date[0]));
}

?>
