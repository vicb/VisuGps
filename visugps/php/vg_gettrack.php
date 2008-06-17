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

require('vg_cfg.php');
require('vg_tracks.php');

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
        generate_kmllive_track($jsonTrack);
        break;
    case 'kml':
        generate_kml_track($jsonTrack);
        break;
    default:
        generate_igc_track($jsonTrack);
    
}

function generate_kmllive_track($jsonTrack) {
    $track = @json_decode($jsonTrack, true);
    if (!isset($track['nbTrackPt']) || $track['nbTrackPt'] < 5) exit;
    
    if (isset($_GET['trackid'])) {
        $trackId = $_GET['trackid'];
    } else {
        exit;
    }
    
    header('Content-Type: application/vnd.google-earth.kml+xml kml; charset=utf8');
    header('Content-Disposition: attachment; filename="track.kml"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    $file= sprintf("<?xml version='1.0' encoding='UTF-8'?>\n" .
                   "<kml xmlns='http://earth.google.com/kml/2.2'>\n" .
                   "  <Folder>\n" .
                   "    <name>GPS Live tracking</name>\n" .
                   "    <visibility>1</visibility>\n" .
                   "    <open>0</open>\n" .
                   "    <NetworkLink>\n" .
                   "      <name>%s</name>\n" .
                   "      <visibility>1</visibility>\n" .
                   "      <open>1</open>\n" .
                   "      <refreshVisibility>0</refreshVisibility>\n" .
                   "      <flyToView>1</flyToView>\n" .
                   "      <Link>\n" .
                   "        <href>%s</href>\n" .
                   "        <httpQuery>trackid=%d&amp;format=kml</httpQuery>\n" .
                   "        <refreshMode>onInterval</refreshMode>\n" .
                   "        <refreshInterval>60</refreshInterval>\n" .
                   "      </Link>\n" .
                   "    </NetworkLink>\n" .
                   "  </Folder>\n" .
                   "</kml>\n",
                   $track['pilot'],
                   "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'],
                   $trackId);
               
    echo $file;

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

    header('Content-Type: application/vnd.google-earth.kml+xml kml; charset=utf8');
    header('Content-Disposition: attachment; filename="track.kml"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

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
                    "        <PolyStyle>\n" .
                    "            <color>7f00ff00</color>\n" .
                    "        </PolyStyle>\n" .
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
                     
    echo $file;
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

    header('Content-type: text/plain; charset=ISO-8859-1');
    header('Content-Disposition: attachment; filename="track.igc"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

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

    echo $file;

}


?>
