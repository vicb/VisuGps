<?php
/*
Script: vg_tracks.php
        Functions to read and write tracks

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

include_once('mvg_db.inc.php');

/*
Function: GetTaskFlights
        Return the list of flight made during last 12 hours

Arguments:
        pattern - Pattern to match pilot IDs
        utcOffset - Offset to add to local time to get UTC time

Returns:
        List of flight IDs
*/
function GetTaskFlights($pattern, $utcOffset = 0) {
    $ids = array();

    $link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
    mysql_select_db(dbName) or die ('Database does not exist');

    // Get flights using UTC time
    $query = "SELECT flightId, COUNT(latitude) as count " .
             "FROM pilot, flight, point " .
             "WHERE start > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 12 HOUR) AND pseudo LIKE '$pattern%' " .
             "AND flightId = flight.id AND pilotID = pilot.id AND utc = 1 " .
             "GROUP BY flightId ORDER BY flightId ASC";
    $result = mysql_query($query) or die('Query error: ' . mysql_error());
    for ($i = 0; $i < mysql_num_rows($result); $i++) {
        $row = mysql_fetch_object($result);
        if ($row->count > 5) {
            $ids[] = $row->flightId;
        }
    }

    // Get flights using local time
    $query = "SELECT flightId, COUNT(latitude) as count " .
             "FROM pilot, flight, point " .
             "WHERE DATE_ADD(start, INTERVAL $utcOffset HOUR) > DATE_SUB(UTC_TIMESTAMP(), INTERVAL 12 HOUR) AND pseudo LIKE '$pattern%' " .
             "AND flightId = flight.id AND pilotID = pilot.id AND utc = 0 " .
             "GROUP BY flightId ORDER BY flightId ASC";
    $result = mysql_query($query) or die('Query error: ' . mysql_error());
    for ($i = 0; $i < mysql_num_rows($result); $i++) {
        $row = mysql_fetch_object($result);
        if ($row->count > 5) {
            $ids[] = $row->flightId;
        }
    }


    return $ids;
}

/*
Function: GetDatabaseTrack
        Return a JSON encoded GPS track form VisuGpsLive database

Arguments:
        trackId - id of the track into the database
        delay - minimum age of fix to return (minute)
        utcOffset - Offset to be added to convert local time to UTC

Returns:
        JSON encoded track. See track format below

Track format:
        The track is an array with the following fields:
        lat - latitudes [Array]
        lon - longitudes [Array]
        elev - track elevations (m) [Array]
        elevGnd - ground elevations (m) [Array]
        speed - speed on the track (km/h) [Array]
        vario - vertical speed (m/s) [Array]
        date - flight date
        pilot - pilot name
        time - time [Array]
            hour - hours [Array]
            min - minutes [Array]
            sec - seconds [Array]
            labels - time as string for graph labels
        nbTrackPt - number of points in lat, lon
        nbChartPt - number of points in elev, elevGnd, speed, vario
        nbChartLbl - number of labels (time.labels)
*/
function GetDatabaseTrack($trackId, $delay = 0, $utcOffset = 0) {
    $link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
    mysql_select_db(dbName) or die ('Database does not exist');

    // Track shoud be long enough to be valid
    $query = "SELECT latitude FROM point WHERE flightId = $trackId";
    $result = mysql_query($query) or die('Query error: ' . mysql_error());
    if (mysql_num_rows($result) > 5) {

        // Get points from the track (using UTC time)
        $query = "SELECT latitude, longitude, elevation, " .
                 "HOUR(time) AS hour, " .
                 "MINUTE(time) AS min, " .
                 "SECOND(time) AS sec, " .
                 "time ".
                 "FROM point, flight " .
                 "WHERE flightId = $trackId " .
                 "AND flight.id = flightId AND utc = 1 " .  // UTC time
                 ($delay > 0?"AND time < DATE_SUB(UTC_TIMESTAMP(), INTERVAL $delay MINUTE) ":"") .
                 "ORDER BY time";
        $result = mysql_query($query) or die('Query error: ' . mysql_error());
        for ($i = 0; $i < mysql_num_rows($result); $i++) {
            $row = mysql_fetch_object($result);
            $track['lat'][$i] = floatval($row->latitude);
            $track['lon'][$i] = floatval($row->longitude);
            $track['elev'][$i] = intval($row->elevation);
            $track['time']['hour'][$i] = intval($row->hour);
            $track['time']['min'][$i] = intval($row->min);
            $track['time']['sec'][$i] = intval($row->sec);
        }
        // Get points from the track (using local time)
        $query = "SELECT latitude, longitude, elevation, " .
                 "HOUR(DATE_ADD(time, INTERVAL $utcOffset HOUR)) AS hour, " .
                 "MINUTE(DATE_ADD(time, INTERVAL $utcOffset HOUR)) AS min, " .
                 "SECOND(DATE_ADD(time, INTERVAL $utcOffset HOUR)) AS sec, " .
                 "time " .
                 "FROM point, flight " .
                 "WHERE flightId = $trackId " .
                 "AND flight.id = flightId AND utc = 0 " .  // Local time
                 ($delay > 0?"AND DATE_ADD(time, INTERVAL $utcOffset HOUR) < DATE_SUB(UTC_TIMESTAMP(), INTERVAL $delay MINUTE) ":"") .
                 "ORDER BY time";
        $result = mysql_query($query) or die('Query error: ' . mysql_error());
        for ($i = 0; $i < mysql_num_rows($result); $i++) {
            $row = mysql_fetch_object($result);
            $track['lat'][$i] = floatval($row->latitude);
            $track['lon'][$i] = floatval($row->longitude);
            $track['elev'][$i] = intval($row->elevation);
            $track['time']['hour'][$i] = intval($row->hour);
            $track['time']['min'][$i] = intval($row->min);
            $track['time']['sec'][$i] = intval($row->sec);
        }

        $track['date'] = array('day' => 0, 'month' => 0, 'year' => 0);
        $track['pilot'] = '';

        $query = "SELECT DAY(start) as day,
                         MONTH(start) as month,
                         YEAR(start) as year,
                         pilotId
                         FROM flight WHERE id = $trackId";
        $result = mysql_query($query) or die('Query error: ' . mysql_error());
        if (mysql_num_rows($result) == 1) {
            $row = mysql_fetch_object($result);
            if (isset($row->day)) $track['date']['day'] = intval($row->day);
            if (isset($row->month)) $track['date']['month'] = intval($row->month);
            if (isset($row->year)) $track['date']['year'] = intval($row->year);
        }

        $query = "SELECT name FROM pilot WHERE id = $row->pilotId";
        $result = mysql_query($query) or die('Query error: ' . mysql_error());
        if (mysql_num_rows($result) == 1) {
            $row = mysql_fetch_object($result);
            $track['pilot'] = $row->name;
        }

        $jsTrack = MakeJsonTrack($track);
    } else {
        $jsTrack['error'] = 'Invalid track';
    }

    $data = @json_encode($jsTrack);
    return $data;
}

/*
Function: MakeTrack
        Return a JSON encoded GPS track

Arguments:
        url - url of the track file

Returns:
        JSON encoded track. See track format below

Track format:
        The track is an array with the following fields:
        lat - latitudes [Array]
        lon - longitudes [Array]
        elev - track elevations (m) [Array]
        elevGnd - ground elevations (m) [Array]
        speed - speed on the track (km/h) [Array]
        vario - vertical speed (m/s) [Array]
        date - flight date
        pilot - pilot name
        time - time [Array]
            hour - hours [Array]
            min - minutes [Array]
            sec - seconds [Array]
            labels - time as string for graph labels
        nbTrackPt - number of points in lat, lon
        nbChartPt - number of points in elev, elevGnd, speed, vario
        nbChartLbl - number of labels (time.labels)
*/
function MakeTrack($url)
{
    require('vg_cache.php');

    $cache = new Cache(CACHE_BASE_FOLDER . CACHE_FOLDER_TRACK, CACHE_NB_TRACK, 9);

    if ($cache->get($data, $url)) {
        return $data;
    } else {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $_GET['track']);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $file = curl_exec($ch);
        curl_close($ch);

        require('vg_parser.php');

        $track['date'] = array('day' => 0, 'month' => 0, 'year' => 0);
        $track['pilot'] = '';

        $nbPts = ParseIgc($file, $track);

        if ($nbPts < 5) {
            $nbPts = ParseGpx($file, $track);
        }
        if ($nbPts < 5) {
            $nbPts = ParseTrk($file, $track);
        }
        if ($nbPts < 5) {
            $nbPts = ParseNmea($file, $track);
        }
        if ($nbPts < 5) {
            $nbPts = ParseOzi($file, $track);
        }

        if ($nbPts < 5) {
            if (IsKml($file)) {
                $jsTrack['kmlUrl'] = $url;
            } else {
                $jsTrack['error'] = 'Unsupported track format!';
            }
        } else {
            $jsTrack = MakeJsonTrack($track);
        }

        $data = @json_encode($jsTrack);
        if (!isset($jsTrack['error']) &&
            !isset($jsTrack['kmlUrl'])) {
            $cache->set($data, $url);
        }

        return $data;
    }
}

/*
Function: MakeJsonTrack
        Convert a track to JSON format

Arguments:
        track - track as associative array.

Return:
        The track in JSON format
*/
function MakeJsonTrack($track) {
    $track['nbPt'] = $nbPts = count($track['lat']);

    // Generate the time in second
    for ($i = 0; $i < count($track['time']['hour']); $i++) {
        $track['timeSec'][$i] = $track['time']['hour'][$i] * 3600 +
                                $track['time']['min'][$i] * 60 +
                                $track['time']['sec'][$i];
    }

    // Generate CHART_NBLBL labels
    for ($i = 0, $idx = 0, $step = ($nbPts - 1) / (CHART_NBLBL - 1); $i < CHART_NBLBL; $i++, $idx += $step) {
        $jsTrack['time']['label'][$i] = sprintf("%02dh%02d", $track['time']['hour'][$idx], $track['time']['min'][$idx]);
    }

    // Change the number of points to CHART_NBPTS
    for ($i = 0, $idx = 0, $step = ($nbPts - 1) / (CHART_NBPTS - 1); $i < CHART_NBPTS; $i++, $idx += $step) {
        $jsTrack['elev'][$i] = $track['elev'][$idx];
        $jsTrack['time']['hour'][$i] = sprintf("%02d", $track['time']['hour'][$idx]);
        $jsTrack['time']['min'][$i] = sprintf("%02d", $track['time']['min'][$idx]);
        $jsTrack['time']['sec'][$i] = sprintf("%02d", $track['time']['sec'][$idx]);
    }

    $jsTrack['lat'] = array();
    foreach ($track['lat'] as $lat) {
        $jsTrack['lat'][] = round($lat, 5);
    }

    $jsTrack['lon'] = array();
    foreach ($track['lon'] as $lon) {
        $jsTrack['lon'][] = round($lon, 5);
    }

    $jsTrack['elevGnd'] = GetElevGnd($track, CHART_NBPTS);
    $jsTrack['speed'] = GetSpeed($track, CHART_NBPTS);
    $jsTrack['vario'] = GetVario($track, CHART_NBPTS);

    $jsTrack['nbTrackPt'] = $track['nbPt'];
    $jsTrack['nbChartPt'] = CHART_NBPTS;
    $jsTrack['nbChartLbl'] = CHART_NBLBL;
    $jsTrack['date'] = $track['date'];
    $jsTrack['pilot'] = $track['pilot'];

    return $jsTrack;
}

/*
Function: GetElevGnd
        Return the ground elevation of the track points

Arguments:
        track - track as associative array.
                [nbPt] point coordinates in [lat] and [lon]
        dstPts - number of points to generate

Return:
        An array of ground elevation in meters
*/
function GetElevGnd($track, $dstPts)
{
    $elevGnd = array_fill(0, $dstPts, 0);

    for ($i = 0, $idx = 0, $step = ($track['nbPt'] - 1) / ($dstPts - 1); $i < $dstPts; $i++, $idx += $step) {
        $lat = $track['lat'][$idx];
        $lon = $track['lon'][$idx];
        $fileLat = (int)floor($lat / SRTM_TILE_SIZE_DEG) * SRTM_TILE_SIZE_DEG;
        $fileLon = (int)floor($lon / SRTM_TILE_SIZE_DEG) * SRTM_TILE_SIZE_DEG;
        $fileName = "strm3_" . $fileLat . "_" . $fileLon . ".strmb";
        $latPx = (int)floor(($fileLat + SRTM_TILE_SIZE_DEG - $lat) * (SRTM_TILE_SIZE_PX - 1) / SRTM_TILE_SIZE_DEG);
        $lonPx = (int)floor(($lon - $fileLon) * (SRTM_TILE_SIZE_PX - 1) / SRTM_TILE_SIZE_DEG);
        $elev[$fileName][$latPx][$lonPx][] = $i;
    }

    foreach ($elev as $file => $lats) {
        $handle = @fopen(SRTM_PATH . $file, "rb");
        if ($handle) {
            foreach ($lats as $latPx => $lons) {
                fseek($handle, $latPx * SRTM_TILE_SIZE_PX);
                $line = fread($handle, SRTM_TILE_SIZE_PX);
                foreach ($lons as $lonPx => $idxs) {
                    foreach ($idxs as $idx) {
                        $elevGnd[$idx] = ord($line[$lonPx]) * 20;
                    }
                }
            }
            fclose($handle);
        }
    }

    return $elevGnd;
}

/*
Function: GetSpeed
        Return the speed on the track

Arguments:
        track - track as associative array.
                [nbPt] point coordinates in [lat] and [lon], timestamp in [timeSec]
        dstPts - number of points to generate

Return:
        An array of speed in km/h
*/
function GetSpeed($track, $dstPts) {
    $speed = array_fill(0, $dstPts, 0);

    for ($i = 0, $idx = 0, $step = ($track['nbPt'] - 1) / ($dstPts - 1); $i < $dstPts; $i++, $idx += $step) {
        $dist = $count = $time = 0;
        $avgidx = $idx;

        while (--$avgidx >= 0 && $count < 65) {
            $deltaT = $track['timeSec'][$idx] - $track['timeSec'][$avgidx];
            if (($count > 0) && ($deltaT > 60)) break;
            $dist += GetDistance($track['lat'][$avgidx],     $track['lon'][$avgidx],
                                 $track['lat'][$avgidx + 1], $track['lon'][$avgidx + 1]);
            $time = $deltaT;
            $count++;
        }

        $speed[$i] = ($time > 0)?floor(3600 * $dist / $time):0;
    }

    return $speed;
}

/*
Function: GetVario
        Return the vertical speed on the track

Arguments:
        track - track as associative array.
                [nbPt] point elevations in [elev], timestamp in [timeSec]
        dstPts - number of points to generate

Return:
        An array of vertical speed in m/s
*/
function GetVario($track, $dstPts) {
    $vario = array_fill(0, $dstPts, 0);

    for ($i = 0, $idx = 0, $step = ($track['nbPt'] - 1) / ($dstPts - 1); $i < $dstPts; $i++, $idx += $step) {
        $elev = $count = $time = 0;
        $avgidx = $idx;

        while (--$avgidx >= 0 && $count < 35) {
            $deltaT = $track['timeSec'][$idx] - $track['timeSec'][$avgidx];
            if (($count > 0) && ($deltaT > 30)) break;
            $elev += $track['elev'][$avgidx + 1] - $track['elev'][$avgidx];
            $time = $deltaT;
            $count++;
        }

        $vario[$i] = ($time > 0)?round($elev / $time, 1):0;
    }

    return $vario;
}

/*
Function: GetDistance
        Return the distance between two points

Arguments:
        lat1 - latitude of the first point
        lat2 - latitude of the second point
        lon1 - longitude of the first point
        lon2 - longitude of the second point
        precision - decimal precision

Return:
        Distance in meters
*/
function GetDistance($lat1, $lon1, $lat2, $lon2, $precision = 4) {
	$lat1 = deg2rad($lat1);
	$lat2 = deg2rad($lat2);
	$lon1 = deg2rad($lon1);
	$lon2 = deg2rad($lon2);
	$theta = $lon1 - $lon2;
	$rawdistance = sin($lat1) * sin($lat2) + cos($lat1) * cos($lat2) * cos($theta);
	$distance = round(6367.2 * acos($rawdistance), $precision);
	return $distance;
}

