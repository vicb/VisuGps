<?php
/*
Script: vg_gettrack.php
        Generate a GPS track from JSON encoded tracks

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

require('vg_cfg.inc.php');
require('vg_tracks.php');
require('ziplib.php');

if (isset($_GET['format'])) {
    $format = $_GET['format'];
} else {
    $format = 'igc';
}

if (isset($_GET['track'])) {
    $jsonTrack = MakeTrack($_GET['track']);
} else if (isset($_GET['trackid'])) {
    $jsonTrack = GetDatabaseTrack(intval($_GET['trackid']));
} else {
    exit;
}

switch ($format) {
    case 'kmllive':
        header('Content-Type: application/vnd.google-earth.kml+xml kml; charset=utf8');
        header('Content-Disposition: attachment; filename="track.kml"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo generate_kmllive_track($jsonTrack);
        break;
    case 'kml':
        header('Content-Type: application/vnd.google-earth.kml+xml kml; charset=utf8');
        header('Content-Disposition: attachment; filename="track.kml"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo generate_kml_track($jsonTrack);
        break;
    case 'kmz':
        header('Content-Type: application/vnd.google-earth.kmz; charset=utf8');
        header('Content-Disposition: attachment; filename="track.kmz"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo generate_kmz_track($jsonTrack);
        break;
    default:
        header('Content-type: text/plain; charset=ISO-8859-1');
        header('Content-Disposition: attachment; filename="track.igc"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo generate_igc_track($jsonTrack);
    
}

function generate_kmllive_track($jsonTrack) {
    $track = @json_decode($jsonTrack, true);
    if (!isset($track['nbTrackPt']) || $track['nbTrackPt'] < 5) exit;
    
    if (isset($_GET['trackid'])) {
        $trackId = $_GET['trackid'];
    } else {
        exit;
    }

    $file= sprintf("<?xml version='1.0' encoding='UTF-8'?>\n" .
                   "<kml xmlns='http://earth.google.com/kml/2.2'>\n" .
                   "  <Folder>\n" .
                   "    <name>GPS Live tracking</name>\n" .
                   "    <visibility>1</visibility>\n" .
                   "    <open>1</open>\n" .
                   "    <NetworkLink>\n" .
                   "      <name>%s</name>\n" .
                   "      <visibility>1</visibility>\n" .
                   "      <open>1</open>\n" .
                   "      <refreshVisibility>0</refreshVisibility>\n" .
                   "      <flyToView>1</flyToView>\n" .
                   "      <Link>\n" .
                   "        <href>%s</href>\n" .
                   "        <httpQuery>trackid=%d&amp;format=kmz</httpQuery>\n" .
                   "        <refreshMode>onInterval</refreshMode>\n" .
                   "        <refreshInterval>60</refreshInterval>\n" .
                   "      </Link>\n" .
                   "    </NetworkLink>\n" .
                   "  </Folder>\n" .
                   "</kml>\n",
                   $track['pilot'],
                   "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
                   $trackId);
               
    return $file;

}

/*
Function: generate_kml_track
        Generate a kml file from a JSON encoded track

Arguments:
        jsonTrack - JSON encoded track

Returns:
        KML file

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
function generate_kml_track($jsonTrack) {
    $track = @json_decode($jsonTrack, true);
    if (!isset($track['nbTrackPt']) || $track['nbTrackPt'] < 5) exit;

    $file = sprintf("<?xml version='1.0' encoding='UTF-8'?>\n" .
                    "<kml xmlns='http://earth.google.com/kml/2.2'>\n" .
                    "<Folder>\n" .
                    "<name>%s</name>\n" .
                    "<LookAt>\n" .
                    "    <longitude>%010.6f</longitude>\n" .
                    "    <latitude>%010.6f</latitude>\n" .
                    "    <range>32000</range>\n" .
                    "    <tilt>64</tilt>\n" .
                    "    <heading>0</heading>\n" .
                    "</LookAt>\n" .
                    "<Placemark>\n" .
                    "    <visibility>1</visibility>\n" .
                    "    <open>1</open>\n" .
                    "    <Style>\n" .
                    "        <LineStyle>\n" .
                    "            <color>ff00ffff</color>\n" .
                    "        </LineStyle>\n" .
                    "    </Style>\n" .
                    "    <LineString>\n" .
                    "        <altitudeMode>absolute</altitudeMode>\n" .
                    "        <coordinates>\n",
                    $track['pilot'],
                    $track['lon'][0],
                    $track['lat'][0]);

    for ($i = 0; $i < $track['nbTrackPt']; $i++) {
        $file .= sprintf("        %010.6f, %010.6f, %05d\n",
                         $track['lon'][$i],
                         $track['lat'][$i],
                         $track['elev'][$i * ($track['nbChartPt'] - 1) / ($track['nbTrackPt'] - 1) ]);
    }
    
    $file = rtrim($file, "\n");

    $file .= sprintf("\n        </coordinates>\n" .
                     "    </LineString>\n" .
                     "</Placemark>\n" .
                     "<Placemark>\n" .
                     "    <name>Deco</name>\n" .
                     "    <Point>\n" .
                     "        <coordinates>\n" .
                     "		      %010.6f, %010.6f, %05d\n" .
                     "        </coordinates>\n" .
                     "    </Point>\n" .
                     "</Placemark>\n" .
                     "<Placemark>\n" .
                     "    <name>Atterro</name>\n" .
                     "    <Point>\n" .
                     "        <coordinates>\n" .
                     "		      %010.6f, %010.6f, %05d\n" .
                     "        </coordinates>\n" .
                     "    </Point>\n" .
                     "</Placemark>\n" .
                     "</Folder>\n" .
                     "</kml>\n",
                     $track['lon'][0],
                     $track['lat'][0],
                     $track['elev'][0],
                     $track['lon'][$track['nbTrackPt'] - 1],
                     $track['lat'][$track['nbTrackPt'] - 1],
                     $track['elev'][$track['nbChartPt'] - 1]);
                     
    return $file;
}


/*
Function: generate_kmz_track
        Generate a kmz file from a JSON encoded track

Arguments:
        jsonTrack - JSON encoded track

Returns:
        KMZ file

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
function generate_kmz_track($jsonTrack) {
    $track = @json_decode($jsonTrack, true);
    if (!isset($track['nbTrackPt']) || $track['nbTrackPt'] < 5) exit;

    $file = sprintf("<?xml version='1.0' encoding='UTF-8'?>\n" .
                    "<kml xmlns='http://earth.google.com/kml/2.2'>\n" .
                    "    <Document>\n" .
                    "        <name><![CDATA[%s]]></name>\n" .
                    "        <LookAt>\n" .
                    "           <longitude>%010.6f</longitude>\n" .
                    "           <latitude>%010.6f</latitude>\n" .
                    "           <range>32000</range>\n" .
                    "           <tilt>64</tilt>\n" .
                    "           <heading>0</heading>\n" .
                    "        </LookAt>\n" .
                    "        <open>1</open>\n" .
                    "        <visibility>1</visibility>\n" .
                    "        <description>\n" .
                    "        Created by VisuGps [www.victorb.fr]\n" .
                    "        </description>\n" .
                    "        <Style id='tracks'>\n" .
                    "            <ListStyle>\n" .
                    "                <listItemType>radioFolder</listItemType>\n" .
                    "            </ListStyle>\n" .
                    "        </Style>\n" .
                    "        <styleUrl>#tracks</styleUrl>\n" .
                    "        <NetworkLink>\n" .
                    "            <open>0</open>\n" .
                    "            <visibility>0</visibility>\n" .
                    "            <name>Plain</name>\n" .
                    "            <Link><href>plain.kml</href></Link>\n" .
                    "        </NetworkLink>\n" .
                    "        <NetworkLink>\n" .
                    "            <open>0</open>\n" .
                    "            <visibility>1</visibility>\n" .
                    "            <name>Altitude</name>\n" .
                    "            <Link><href>altitude.kml</href></Link>\n" .
                    "        </NetworkLink>\n" .
                    "        <NetworkLink>\n" .
                    "            <open>0</open>\n" .
                    "            <visibility>0</visibility>\n" .
                    "            <name>Speed</name>\n" .
                    "            <Link><href>speed.kml</href></Link>\n" .
                    "        </NetworkLink>\n" .
                    "        <NetworkLink>\n" .
                    "            <open>0</open>\n" .
                    "            <visibility>0</visibility>\n" .
                    "            <name>Vario</name>\n" .
                    "            <Link><href>vario.kml</href></Link>\n" .
                    "        </NetworkLink>\n" .
                    "    </Document>\n" .
                    "</kml>", $track['pilot'], 
                    $track['lon'][0],
                    $track['lat'][0]);

    $zip = new zipfile();
    $zip->addFile($file, "doc.kml");
    $zip->addFile(generate_kml_track($jsonTrack), "plain.kml");
    $zip->addFile(generate_colored_track($jsonTrack, 'elev', 'm'), "altitude.kml");
    $zip->addFile(generate_colored_track($jsonTrack, 'vario', 'm/s'), "vario.kml");
    $zip->addFile(generate_colored_track($jsonTrack, 'speed', 'km/h'), "speed.kml");

    return $zip->file();
}

/*
Function: generate_colored_track
        Generate a kmz file from a JSON encoded track

Arguments:
        jsonTrack - JSON encoded track
        idxSerie - Index of the serie to be drawn
        unit - Unit of the serie

Returns:
        KML file as string

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
function generate_colored_track($jsonTrack, $idxSerie, $unit) {
    $track = @json_decode($jsonTrack, true);

    $minValue = 20000;
    $maxValue = -20000;

    for ($i = 0; $i < count($track[$idxSerie]); $i++) {
        $minValue = min($minValue, $track[$idxSerie][$i]);
        $maxValue = max($maxValue, $track[$idxSerie][$i]);
    }

    $file = sprintf("<?xml version='1.0' encoding='UTF-8'?>\n" .
                    "<kml xmlns='http://earth.google.com/kml/2.2'>\n" .
                    "<Folder>\n" .
                    "<name>%s</name>\n" .
                    "<LookAt>\n" .
                    "    <longitude>%010.6f</longitude>\n" .
                    "    <latitude>%010.6f</latitude>\n" .
                    "    <range>32000</range>\n" .
                    "    <tilt>64</tilt>\n" .
                    "    <heading>0</heading>\n" .
                    "</LookAt>\n",
                    $track['pilot'],
                    $track['lon'][0],
                    $track['lat'][0]);

    $point = 0;
    while(1) {
        if ($point >= $track['nbTrackPt']) break;
        $chartIndex = $point * ($track['nbChartPt'] - 1) / ($track['nbTrackPt'] - 1);
        $file .= sprintf("<Placemark>\n" .
                 "    <visibility>1</visibility>\n" .
                 "    <open>1</open>\n" .
                 "    <name>%02d:%02d:%02d - %.1f%s</name>\n" .
                 "    <Style>\n" .
                 "        <LineStyle>\n" .
                 "            <color>FF%s</color>\n" .
                 "        </LineStyle>\n" .
                 "    </Style>\n" .
                 "    <TimeStamp>\n" .
				 "        <when>%04d-%02d-%02dT%02d:%02d:%02d+00:00</when>\n".
				 "    </TimeStamp>\n" .
                 "    <LineString>\n" .
                 "        <altitudeMode>absolute</altitudeMode>\n" .
                 "        <coordinates>\n",
                 $track['time']['hour'][$chartIndex],
                 $track['time']['min'][$chartIndex],
                 $track['time']['sec'][$chartIndex],
                 $track[$idxSerie][$chartIndex],
                 $unit,
                 value2color($track[$idxSerie][$chartIndex], $minValue, $maxValue),
                 $track['date']['year'],
                 $track['date']['month'],
                 $track['date']['day'],
                 $track['time']['hour'][$chartIndex],
                 $track['time']['min'][$chartIndex],
                 $track['time']['sec'][$chartIndex]);
        for ($i = 0; $i < 5; $i++) {
            if (($point + $i) == $track['nbTrackPt']) break;
            $file .= sprintf("        %010.6f, %010.6f, %05d\n",
                             $track['lon'][$point + $i],
                             $track['lat'][$point + $i],
                             $track['elev'][($point + $i) * ($track['nbChartPt'] - 1) / ($track['nbTrackPt'] - 1) ]);
        }
        $point += 4;
        $file .= "        </coordinates>\n" .
                 "    </LineString>\n" .
                 "</Placemark>\n";
    }

    $file .= sprintf("<Placemark>\n" .
                     "    <name>Deco</name>\n" .
                     "    <Point>\n" .
                     "        <coordinates>\n" .
                     "		      %010.6f, %010.6f, %05d\n" .
                     "        </coordinates>\n" .
                     "    </Point>\n" .
                     "</Placemark>\n" .
                     "<Placemark>\n" .
                     "    <name>Atterro</name>\n" .
                     "    <Point>\n" .
                     "        <coordinates>\n" .
                     "		      %010.6f, %010.6f, %05d\n" .
                     "        </coordinates>\n" .
                     "    </Point>\n" .
                     "</Placemark>\n" .
                     "</Folder>\n" .
                     "</kml>\n",
                     $track['lon'][0],
                     $track['lat'][0],
                     $track['elev'][0],
                     $track['lon'][$track['nbTrackPt'] - 1],
                     $track['lat'][$track['nbTrackPt'] - 1],
                     $track['elev'][$track['nbChartPt'] - 1]);
                     
    return $file;
}

/*
Function: generate_igc_track
        Generate a igc file from a JSON encoded track

Arguments:
        jsonTrack - JSON encoded track

Returns:
        IGC file

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

function generate_igc_track($jsonTrack) {
    $track = @json_decode($jsonTrack, true);
    if (!isset($track['nbTrackPt']) || $track['nbTrackPt'] < 5) exit;



    $file = sprintf("AXXXXXX\n" .
                    "HFDTE%02d%02d%02d\n" .
                    "HFPLTPILOT:%s\n" .
                    "HFDTM100GPSDATUM:WGS-1984\n",
                    $track['date']['day'],
                    $track['date']['month'],
                    $track['date']['year'],
                    $track['pilot']);
                    
    for ($i = 0; $i < $track['nbTrackPt']; $i++) {
        $chartIdx = $i * ($track['nbChartPt'] - 1) / ($track['nbTrackPt'] - 1);
        $lat = intval($track['lat'][$i]) + ($track['lat'][$i] - intval($track['lat'][$i])) * 60 / 100;
        $lon = intval($track['lon'][$i]) + ($track['lon'][$i] - intval($track['lon'][$i])) * 60 / 100;
        $file .= sprintf("B%02d%02d%02d%07d%s%08d%sA%05d%05d\n",
                         $track['time']['hour'][$chartIdx],
                         $track['time']['min'][$chartIdx],
                         $track['time']['sec'][$chartIdx],
                         intval(abs($lat) * 100000),
                         $lat > 0?'N':'S',
                         intval(abs($lon) * 100000),
                         $lon > 0?'E':'W',
                         $track['elev'][$chartIdx],
                         $track['elev'][$chartIdx]);
    }

    return $file;

}

/*
Function: value2color
        convert a value to corresponding color

Arguments:
        value - value
        min - lower bound
        max - upper bound

Returns:
        IGC file
*/
function value2color($value, $min, $max) {
    $Mm = $max - $min;
    $xm = $value - $min;

    if ($min * $max < 0) {
        if ($min == 0) $min = 1;
        if ($max == 0) $max = 1;
        $value *= 1.25;
        if ($value > $max) $value = $max;
        if ($value < $min) $value = $min;
        if ($value >= 0)
            $k = sprintf("%02X", 0xFF * (1 - $value / $max)) . "00FF";
        else
            $k = "FF00" . sprintf("%02X", 0xFF * (1 - $value / $min)) ;
    } else {
      if ($Mm == 0) $Mm = 1;
      if ($xm >= 2 * $Mm / 3)
          $k = '00' . sprintf("%02X",0xFF*3*(1 - $xm / $Mm)) . 'FF';
      elseif ($xm >= $Mm/2)
          $k = '00FF' . sprintf("%02X", 0xFF * (6 * $xm / $Mm - 3));
      elseif ($xm >= $Mm/3)
          $k = sprintf("%02X", 0xFF * (3 - 6 * $xm / $Mm)) . 'FF00';
      else
          $k = 'FF' . sprintf("%02X", 0xFF * 3 * $xm / $Mm) . '00';
    }
    return $k;
}

?>
