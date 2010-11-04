<?php
/*
Script: vg_parser.php
        GPS track file parsers.

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

Credits:
    - Some of GPX, NMEA and TRK parsing routines are from Emmanuel Chabani <mans@parawing.net>
*/

/*
Function: ParseIgc
        Parse a GPS track - IGC format
        
Arguments:
        trackFile - input track file
        trackData - output track (associative array)
        
Returns:
        The number of points of the track.
        0 if the track format is not recognized
*/
function ParseIgc($trackFile, &$trackData)
{
    if (preg_match('/HFDTE(\d{2})(\d{2})(\d{2})/mi', $trackFile, $m)) {
        $trackData['date']['day'] = intval($m[1]);
        $trackData['date']['month'] = intval($m[2]);
        $trackData['date']['year'] = intval($m[3]) + (($m[3] > 60)?1900:2000);
    }

    if (preg_match('/HFPLTPILOT:([\x20-\x7e\x80-\xfe]+)/mi', $trackFile, $m)) {
        $trackData['pilot'] = htmlentities(trim($m[1]));
    }

    preg_match_all(
      '/B
      (?<hour>\d{2})(?<min>\d{2})(?<sec>\d{2})
      (?<latE>\d{2})(?<latD>\d{5})(?<latS>\w)
      (?<lonE>\d{3})(?<lonD>\d{5})(?<lonS>\w).
      (?<elevP>\d{5})(?<elevG>\d{5})
      /xim',
      $trackFile, 
      $m
    );

    $nbPts = $trackData['nbPt'] = count($m[0]);

    if ($nbPts > 5) {
        // Extract latitude, longitude, altitudes and time in second
        for ($i = 0; $i < $nbPts; $i++) {
            $m['latD'][$i] = ("0." . $m['latD'][$i]) * 100 / 60;
            $m['lonD'][$i] = ("0." . $m['lonD'][$i]) * 100 / 60;
            $trackData['lat'][$i] = ($m['latE'][$i] + $m['latD'][$i]) * (strtoupper($m['latS'][$i]) == 'N'?1:-1);
            $trackData['lon'][$i] = ($m['lonE'][$i] + $m['lonD'][$i]) * (strtoupper($m['lonS'][$i]) == 'E'?1:-1);
            $trackData['elev'][$i] = intval($m['elevG'][$i]);
            $trackData['time']['hour'][$i] = intval($m['hour'][$i]);
            $trackData['time']['min'][$i] = intval($m['min'][$i]);
            $trackData['time']['sec'][$i] = intval($m['sec'][$i]);
        }
    }
    return $nbPts;
}

/*
Function: ParseOzi
        Parse a GPS track - OziExplorer PLT format

See: http://www.rus-roads.ru/gps/help_ozi/fileformats.html

Arguments:
        trackFile - input track file
        trackData - output track (associative array)

Returns:
        The number of points of the track.
        0 if the track format is not recognized
*/
function ParseOzi($trackFile, &$trackData)
{
    if (!preg_match('/OziExplorer/i', $trackFile, $m)) {
        return 0;
    }

    preg_match_all(
      '/^\s+
      (?<lat>[-\d\.]+)[,\s]+
      (?<lon>[-\d\.]+)[,\s]+[01][,\s]+
      (?<elev>[-\d\.]+)[,\s]+
      (?<date>[\d\.]+).*$
      /xim',
      $trackFile,
      $m
    );

    $nbPts = $trackData['nbPt'] = count($m[0]);

    if ($nbPts > 5) {
        // Extract latitude, longitude, altitudes and time in second
        for ($i = 0; $i < $nbPts; $i++) {
            $trackData['lat'][$i] = floatval($m['lat'][$i]);
            $trackData['lon'][$i] = floatval($m['lon'][$i]);
            $trackData['elev'][$i] = max(intval($m['elev'][$i] * 0.3048), 0);

            $time = floatval($m['date'][$i]) - intval($m['date'][$i]);
            $time = $time * 24;
            $hour = intval($time);
            $time = ($time - $hour) * 60;
            $min = intval($time);
            $time = ($time - $min) * 60;
            $sec = intval($time);
            $trackData['time']['hour'][$i] = $hour;
            $trackData['time']['min'][$i] = $min;
            $trackData['time']['sec'][$i] = $sec;
        }
    $date = date_create();
    date_date_set($date, 1899, 12, 30);
    date_modify($date, intval($m['date'][0]) . ' days');
    $trackData['date']['day'] = intval(date_format($date, 'j'));
    $trackData['date']['month'] = intval(date_format($date, 'n'));
    $trackData['date']['year'] = intval(date_format($date, 'Y'));

    }
    return $nbPts;
}

/*
Function: ParseTrk
        Parse a GPS track - TRK format

Arguments:
        trackFile - input track file
        trackData - output track (associative array)

Returns:
        The number of points of the track.
        0 if the track format is not recognized
*/
function ParseTrk($trackFile, &$trackData)
{
    preg_match_all(
      '/^T\s+A\s+
      (?<lat>[0-9\.]+).(?<latS>\w)\s+
      (?<lon>[0-9\.]+).(?<lonS>\w)\s+
      (?<day>\d{2})-(?<month>\w{3})-(?<year>\d{2})\s+
      (?<hour>\d{2}):(?<min>\d{2}):(?<sec>\d{2})\s+.\s+
      (?<elev>\d+)
      /xim',
      $trackFile, 
      $m
    );

    if (count($m[0] < 5 ))
    {
      preg_match_all(
        '/^T\s+
        (?<latS>\w)(?<lat>[0-9\.]+)\s+
        (?<lonS>\w)(?<lon>[0-9\.]+)\s+
        (?<day>\d{2})-(?<month>\w{3})-(?<year>\d{2})\s+
        (?<hour>\d{2}):(?<min>\d{2}):(?<sec>\d{2})\s+
        (?<elev>\d+)
        /xim',
        $trackFile,
        $m
      );
    }

    $nbPts = $trackData['nbPt'] = count($m[0]);

    if ($nbPts > 5) {
        // Extract latitude, longitude, altitudes and time in second
        for ($i = 0; $i < $nbPts; $i++) {
            $trackData['lat'][$i] = ($m['lat'][$i]) * (strtoupper($m['latS'][$i]) == 'N'?1:-1);
            $trackData['lon'][$i] = ($m['lon'][$i]) * (strtoupper($m['lonS'][$i]) == 'E'?1:-1);
            $trackData['elev'][$i] = intval($m['elev'][$i]);
            $trackData['time']['hour'][$i] = intval($m['hour'][$i]);
            $trackData['time']['min'][$i] = intval($m['min'][$i]);
            $trackData['time']['sec'][$i] = intval($m['sec'][$i]);
        }

        $months = array('JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4, 'MAY' => 5, 'JUN' => 6, 
                        'JUL' => 7, 'AUG' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12);
        $trackData['date']['day'] = intval($m['day'][0]);
        $month = strtoupper($m['month'][0]);
        $trackData['date']['month'] = isset($months[$month]) ? $months[$month] : 1;
        $trackData['date']['year'] = intval($m['year'][0]) + (($m['year'][0] > 60)?1900:2000);
        
    }
    return $nbPts;
}

/*
Function: ParseNmea
        Parse a GPS track - NMEA format

Arguments:
        trackFile - input track file
        trackData - output track (associative array)

Returns:
        The number of points of the track.
        0 if the track format is not recognized
*/
function ParseNmea($trackFile, &$trackData)
{
    // Regexp fields
    define('NMEA_hour', 1);
    define('NMEA_min', 2);
    define('NMEA_sec', 3);
    define('NMEA_lat', 4);
    define('NMEA_latS', 5);
    define('NMEA_lon', 6);
    define('NMEA_lonS', 7);
    define('NMEA_elev', 8);

    if (preg_match('/^\$GPRMC,\d+,.,[\d\.]+,.,[\d\.]+,.,[\d\.]+,[\d\.]+,(\d{2})(\d{2})(\d{2})/mi', $trackFile, $m)) {
        $trackData['date']['day'] = intval($m[1]);
        $trackData['date']['month'] = intval($m[2]);
        $trackData['date']['year'] = intval($m[3]) + (($m[3] > 60)?1900:2000);
    }

    preg_match_all('/^\$GPGGA,(\d{2})(\d{2})(\d{2})[\d\.]*,([\d\.]+),(\w),([\d\.]+),(\w),\d+,\d+,[\d\.]+,([\d\.]+)/im',
                   $trackFile, $m);

    $nbPts = $trackData['nbPt'] = count($m[0]);

    if ($nbPts > 5) {
        // Extract latitude, longitude, altitudes and time in second
        for ($i = 0; $i < $nbPts; $i++) {
            $lonDeg= intval($m[NMEA_lon][$i] / 100);
            $lonMin= $m[NMEA_lon][$i] - $lonDeg * 100;
            $latDeg= intval($m[NMEA_lat][$i] / 100);
            $latMin= $m[NMEA_lat][$i] - $latDeg * 100;
            $trackData['lat'][$i] = ($latDeg + $latMin / 60) * (strtoupper($m[NMEA_latS][$i]) == 'N'?1:-1);
            $trackData['lon'][$i] = ($lonDeg + $lonMin / 60) * (strtoupper($m[NMEA_lonS][$i]) == 'E'?1:-1);
            $trackData['elev'][$i] = intval($m[NMEA_elev][$i]);
            $trackData['time']['hour'][$i] = intval($m[NMEA_hour][$i]);
            $trackData['time']['min'][$i] = intval($m[NMEA_min][$i]);
            $trackData['time']['sec'][$i] = intval($m[NMEA_sec][$i]);
        }
    }
    return $nbPts;
}

/*
Function: ParseGpx
        Parse a GPS track - GPX format

Arguments:
        trackFile - input track file
        trackData - output track (associative array)

Returns:
        The number of points of the track.
        0 if the track format is not recognized
*/
function ParseGpx($trackFile, &$trackData) 
{

    if (!($xml = @simplexml_load_string($trackFile))) return 0;

    if (!isset($xml->trk[0]->trkseg[0]->trkpt[0])) return 0;

    $dateSet = false;
    $i = $ptLat = $ptLon = $ptElev = $ptHour = $ptMin = $ptSec = 0;

    $trkIdx = $gpsTrkIdx = 0;
    foreach ($xml->trk as $track) {
        if (isset($track->name) &&
            (strtoupper($track->name) === 'GNSSALTTRK')) {
            $gpsTrkIdx = $trkIdx;
            break;
        }
        $trkIdx++;
    }
    
    foreach ($xml->trk[$gpsTrkIdx]->trkseg as $trackSeg) {
        foreach ($trackSeg->trkpt as $trackPt) {
            $atr = $trackPt->attributes();
            if (isset($atr->lat)) $ptLat = floatval($atr->lat);
            if (isset($atr->lon)) $ptLon = floatval($atr->lon);                 

            if (isset($trackPt->ele)) $ptElev = round($trackPt->ele);
            if (isset($trackPt->time)) {
                if (preg_match('/(\d{2}):(\d{2}):(\d{2})/', $trackPt->time, $m)) {
                    $ptHour = intval($m[1]);
                    $ptMin = intval($m[2]);
                    $ptSec = intval($m[3]);
                }
                if (!$dateSet &&
                    preg_match('/(\d{4})-(\d{2})-(\d{2})/', $trackPt->time, $m)) {
                    $dateSet = true;
                    $trackData['date']['year'] = intval($m[1]);
                    $trackData['date']['month'] = intval($m[2]);
                    $trackData['date']['day'] = intval($m[3]);
                }
            }
            $trackData['lat'][$i] = $ptLat;
            $trackData['lon'][$i] = $ptLon;
            $trackData['elev'][$i] = $ptElev;
            $trackData['time']['hour'][$i] = $ptHour;
            $trackData['time']['min'][$i] = $ptMin;
            $trackData['time']['sec'][$i] = $ptSec;
            $i++;
        }
    }

    $trackData['nbPt'] = $i;
    
    return $i;
}

/*
Function: IsKml
        Detect KML file format

Arguments:
        trackFile - input track file

Returns:
        true if the file is a valid KML file
*/
function IsKml($trackFile)
{
    if (preg_match('/xmlns *= *["\']http:\/\/.*?\/kml\/[\d\.]+/im', $trackFile) > 0) {
        return true;
    } elseif (preg_match('/GpsDump/im', $trackFile) > 0 &&
              preg_match('/<LineString>/im', $trackFile) > 0) {
        // GpsDump generates invalid kml files!        
        return true;
    }
        
}

?>
