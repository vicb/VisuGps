<?php
/*
Script: vg_proxy.php
        Download a GPS track and return it in a JSON format

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

Copyright (c) 2007 Victor Berchet, <http://www.victorb.fr>

*/


header('Content-type: text/plain; charset=ISO-8859-1');
//header('Cache-Control: no-cache, must-revalidate');
//header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

require('vg_cfg.php');

if (isset($_GET['track'])) {
    echo MakeTrack($_GET['track']);
} else if (isset($_GET['trackid'])) {
    echo GetDatabaseTrack(intval($_GET['trackid']));
} else {
    echo @json_encode(array('error' => 'invalid URL'));
}

function GetDatabaseTrack($trackId) {
    include_once('mvg_db.inc.php');

    $link = mysql_connect(dbHost, dbUser, dbPassword) or die ('Could not connect: ' . mysql_error());
    mysql_select_db(dbName) or die ('Database does not exist');

    // Track shoud be long enough to be valid
    $query = "SELECT latitude FROM point WHERE flightId = $trackId";
    $result = mysql_query($query) or die('Query error: ' . mysql_error());
    if (mysql_num_rows($result) > 5) {

        // Get the pilot id when it exists otherwise exit
        $query = "SELECT latitude, longitude, elevation, DATE_FORMAT(time, '%H') AS hour, " .
                 "DATE_FORMAT(time, '%i') AS min, DATE_FORMAT(time, '%S') AS sec, time " .
                 "FROM point WHERE flightId = $trackId ORDER BY time";
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

        $query = "SELECT DATE_FORMAT(start, '%d') as day,
                         DATE_FORMAT(start, '%c') as month,
                         DATE_FORMAT(start, '%Y') as year,
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
        $jsTrack['time']['label'][$i] = $track['time']['hour'][$idx] . "h" . $track['time']['min'][$idx];
    }

    // Change the number of points to CHART_NBPTS
    for ($i = 0, $idx = 0, $step = ($nbPts - 1) / (CHART_NBPTS - 1); $i < CHART_NBPTS; $i++, $idx += $step) {
        $jsTrack['elev'][$i] = $track['elev'][$idx];
        $jsTrack['time']['hour'][$i] = $track['time']['hour'][$idx];
        $jsTrack['time']['min'][$i] = $track['time']['min'][$idx];
        $jsTrack['time']['sec'][$i] = $track['time']['sec'][$idx];
    }

    $jsTrack['lat'] = $track['lat'];
    $jsTrack['lon'] = $track['lon'];

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

?>
