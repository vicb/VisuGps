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
    if ($format != 'kmllive') {
        // Do not generate the track for live formats
        $jsonTrack = GetDatabaseTrack(intval($_GET['trackid']));
    }
} else if ($format == 'tasklive' || $format == 'task') {
    $task = isset($_GET['task'])?$_GET['task']:'task';
    $delay = isset($_GET['delay'])?intval($_GET['delay']):10;
    $utcOffset = isset($_GET['utc'])?intval($_GET['utc']):0;
} else {
    exit;
}

switch ($format) {
    case 'kmllive':
        if (isset($_GET['trackid'])) {
            $trackId = $_GET['trackid'];
        } else {
            exit;
        }
        header('Content-Type: application/vnd.google-earth.kml+xml kml; charset=utf8');
        header('Content-Disposition: attachment; filename="track.kml"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo generate_kmllive_track($trackId);
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
    case 'tasklive':
        header('Content-Type: application/vnd.google-earth.kml+xml kml; charset=utf8');
        header('Content-Disposition: attachment; filename="track.kml"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo generate_kml_tasklive($task, $delay, $utcOffset);
        break;
    case 'task':
        header('Content-Type: application/vnd.google-earth.kml+xml kml; charset=utf8');
        header('Content-Disposition: attachment; filename="track.kml"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo generate_kml_task($task, $delay, $utcOffset);
        break;
    default:
        header('Content-type: text/plain; charset=ISO-8859-1');
        header('Content-Disposition: attachment; filename="track.igc"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        echo generate_igc_track($jsonTrack);
    
}

/*
Function: generate_kmllive_track
        Generate a auto-refreshed kml file

Arguments:
        id - Track id

Returns:
        KML file
*/
function generate_kmllive_track($id) {

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
                   $id);
               
    return $file;
}

/*
Function: generate_kml_track
        Generate a kml file from a JSON encoded track

Arguments:
        jsonTrack - JSON encoded track
        color - Track color

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
function generate_kml_track($jsonTrack, $color = "ff00ffff") {
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
                    "</LookAt>\n",
                    $track['pilot'],
                    $track['lon'][0],
                    $track['lat'][0]);
                    
    $file .= generate_kml_linestring('Track', $track, $color);

    $file .= sprintf("<Placemark>\n" .
                     "    <name>Deco</name>\n" .
                     "    <Point>\n" .
                     "        <coordinates>\n" .
                     "          %010.6f, %010.6f, %05d\n" .
                     "        </coordinates>\n" .
                     "    </Point>\n" .
                     "</Placemark>\n" .
                     "<Placemark>\n" .
                     "    <name>Atterro</name>\n" .
                     "    <Point>\n" .
                     "        <coordinates>\n" .
                     "          %010.6f, %010.6f, %05d\n" .
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
Function: generate_kml_linestring
        Generate a linestring Placemark (KML format)

Arguments:
        name - Name of the linestring
        track - GPS track
        color - Linestring color

Returns:
        KML fragment
*/
function generate_kml_linestring($name, $track, $color, $width = 1) {
    $line = "<Placemark>\n" .
            "    <name>$name</name>\n" .
            "    <visibility>1</visibility>\n" .
            "    <open>1</open>\n" .
            "    <Style>\n" .
            "        <LineStyle>\n" .
            "            <color>$color</color>\n" .
            "            <width>$width</width>\n" .
            "        </LineStyle>\n" .
            "    </Style>\n" .
            "    <LineString>\n" .
            "        <altitudeMode>absolute</altitudeMode>\n" .
            "        <coordinates>\n";

    for ($i = 0; $i < $track['nbTrackPt']; $i++) {
        $line .= sprintf("        %010.6f, %010.6f, %05d\n",
                         $track['lon'][$i],
                         $track['lat'][$i],
                         get_track_elevation($track, $i));
    }

    $line .= "        </coordinates>\n" .
             "    </LineString>\n" .
             "</Placemark>\n";

    return $line;
}

/*
Function: generate_kml_point
        Generate a point Placemark (KML format)

Arguments:
        name - Name of the point
        lat, lon, elev - Coordinates of the point

Returns:
        KML fragment
*/
function generate_kml_point($name, $lat, $lon, $elev) {
    $point = sprintf("<Placemark>\n" .
                     "    <name><![CDATA[$name]]></name>\n" .
                     "    <styleUrl>#picon</styleUrl>\n" .
                     "    <Point>\n".
                     "        <extrude>1</extrude>\n" .
                     "        <altitudeMode>absolute</altitudeMode>\n" .
                     "        <coordinates>\n" .
                     "            %010.6f, %010.6f, %05d\n" .
                     "        </coordinates>\n" .
                     "    </Point>\n".
                     "</Placemark>\n",
                     $lon, $lat, $elev);

    return $point;
}


/*
Function: get_track_elevation
        Interpolate altitude (there's more lat/lon than elevation data)

Arguments:
        track - GPS track
        index - index of the point

Returns:
        Interpolated altitude
*/
function get_track_elevation($track, $index){
        $index = $index * ($track['nbChartPt'] - 1) / ($track['nbTrackPt'] - 1);
        $i = round($index);
        $j = $i + 1;
        if ($j >= $track['nbChartPt']) $j = $track['nbChartPt'] - 1;
        return $track['elev'][$i] + ($index - $i) * ($track['elev'][$j] - $track['elev'][$i]);
}


/*
Function: generate_kml_tasklive
        Generate a auto-refreshed kml file for a task

Arguments:
        task - Task name pattern
        delay - Track delay in minutes
        utcOffset - Offset to add to local time to get UTC time

Returns:
        KML file
*/
function generate_kml_tasklive($task, $delay, $utcOffset) {
    $file= sprintf("<?xml version='1.0' encoding='UTF-8'?>\n" .
                   "<kml xmlns='http://earth.google.com/kml/2.2'>\n" .
                   "  <Folder>\n" .
                   "    <name>GPS Live tracking</name>\n" .
                   "    <visibility>1</visibility>\n" .
                   "    <open>1</open>\n" .
                   "    <NetworkLink>\n" .
                   "      <name>Task</name>\n" .
                   "      <visibility>1</visibility>\n" .
                   "      <open>1</open>\n" .
                   "      <refreshVisibility>0</refreshVisibility>\n" .
                   "      <flyToView>0</flyToView>\n" .
                   "      <Link>\n" .
                   "        <href>%s</href>\n" .
                   "        <httpQuery>delay=$delay&amp;format=task&amp;task=$task&amp;utc=$utcOffset</httpQuery>\n" .
                   "        <refreshMode>onInterval</refreshMode>\n" .
                   "        <refreshInterval>10</refreshInterval>\n" .
                   "      </Link>\n" .
                   "    </NetworkLink>\n" .
                   "  </Folder>\n" .
                   "</kml>\n",
                   "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']);

    return $file;
}

/*
Function: generate_kml_task
        Generate a kml file for a task with multiple pilots

Arguments:
        task - Task name pattern
        delay - Track delay in minutes
        utcOffset - Offset to add to local time to get UTC time

Returns:
        KML file
*/

function generate_kml_task($task, $delay, $utcOffset) {
    $file = "<?xml version='1.0' encoding='UTF-8'?>\n" .
            "<kml xmlns='http://earth.google.com/kml/2.2'>\n" .
            "<Document>\n" .
            "<Folder>\n" .
            "<Style id='picon'>\n" .
            "    <IconStyle>\n" .
            "        <Icon>\n" .
            "            <href>http://victorb.fr/visugps/img/picon.png</href>\n" .
            "            <hotSpot x='50'  y='0' xunits='pixels' yunits='pixels'/>\n" .
            "        </Icon>\n" .
            "    </IconStyle>\n" .
            "</Style>\n" .
            "<name>Pilots</name>\n";
                    
    $ids = GetTaskFlights($task, $utcOffset);
    
    $maxPilots = max(5, count($ids));
    
    for ($i = 0; $i < count($ids); $i++) {
          $jsonTrack = GetDatabaseTrack($ids[$i], $delay, $utcOffset);
          $track = @json_decode($jsonTrack, true);
          $color = 'FF' . value2color($i, 0, $maxPilots);
          if (isset($track['nbTrackPt']) && $track['nbTrackPt'] > 5) {
              $file .= generate_kml_linestring($track['pilot'], $track, $color, 2);
              $file .= generate_kml_point($track['pilot'], end($track['lat']), end($track['lon']), end($track['elev']));
          }
    }
/*    
    $file .= "</Folder><Folder><name>Turn points</name>\n" .
    "<Placemark>\n" .
    "    <name>Start 12:30</name>\n" .
    "    <Point>\n" .
    "        <coordinates>\n" .
    "        6.456398, 44.013995, 3000\n" .
    "        </coordinates>\n" .
    "    </Point>\n" .
    "</Placemark>\n" .
    "<Placemark>\n" .
    "    <name>B2</name>\n" .
    "    <Point>\n" .
    "        <coordinates>\n" .
    "        6.470938, 43.961758, 3000\n" .
    "        </coordinates>\n" .
    "    </Point>\n" .
    "</Placemark>\n" .
    "<Placemark>\n" .
    "    <name>B3</name>\n" .
    "    <Point>\n" .
    "        <coordinates>\n" .
    "        6.443883, 44.047477, 3000\n" .
    "        </coordinates>\n" .
    "    </Point>\n" .
    "</Placemark>\n" .
    "<Placemark>\n" .
    "    <name>B4</name>\n" .
    "    <Point>\n" .
    "        <coordinates>\n" .
    "        6.383936, 43.961208, 3000\n" .
    "        </coordinates>\n" .
    "    </Point>\n" .
    "</Placemark>\n" .
    "<Placemark>\n" .
    "    <name>B5</name>\n" .
    "    <Point>\n" .
    "        <coordinates>\n" .
    "        6.557838, 43.944109, 3000\n" .
    "        </coordinates>\n" .
    "    </Point>\n" .
    "</Placemark>\n" .    
    "<Placemark>\n" .
    "    <name>Goal</name>\n" .
    "    <Point>\n" .
    "        <coordinates>\n" .
    "        6.510494, 43.958585, 3000\n" .
    "        </coordinates>\n" .
    "    </Point>\n" .
    "</Placemark>\n" .
    "</Folder>\n</Document>\n</kml>";
*/    
    
    $file .= "</Folder>\n" .
             "</Document>\n" .
             "</kml>";

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

    $zip = new zipfile();
    $plain = generate_kml_track($jsonTrack);
    $altitude = generate_colored_track($jsonTrack, 'elev', 'm', $minAlti, $maxAlti);
    $vario = generate_colored_track($jsonTrack, 'vario', 'm/s', $minVario, $maxVario);
    $speed = generate_colored_track($jsonTrack, 'speed', 'km/h', $minSpeed, $maxSpeed);

    $description = "Created by VisuGps [www.victorb.fr]<br/><br/>\n" .
                   "<table width=300><tr><td align='left'>$minAlti</td><td align='right'>${maxAlti}m</td></tr>\n" .
                   "<tr><td colspan=2><img src='scale.png' height=30 width=300></td></tr>\n" .
                   "<tr><td align='left'>$minSpeed</td><td align='right'>${maxSpeed}km/h</td></tr>\n" .
                   "<tr><td colspan=2><img src='scale.png' height=30 width=300></td></tr>\n" .
                   "<tr><td align='left'>$minVario</td><td align='right'>${maxVario}m/s</td></tr>\n" .
                   "<tr><td colspan=2><img src='scale.png' height=30 width=300></td></tr>\n";

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
                    "            <![CDATA[%s]]>\n" .
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
                    $track['lat'][0],
                    $description);

    $zip->addFile($file, "main.kml");
    $zip->addFile(create_scale_image(300, 30), "scale.png");
    $zip->addFile($plain, "plain.kml");
    $zip->addFile($altitude, "altitude.kml");
    $zip->addFile($vario, "vario.kml");
    $zip->addFile($speed, "speed.kml");

    return $zip->file();
}


/*
Function: create_scale_image
        Create an image representing the scale

Arguments:
        $width - image width
        $height - image height

Returns:
        The scale image as a string

*/
function create_scale_image($width, $height) {
    $im = imagecreatetruecolor($width, $height);
    $k = imagecolorallocate($im, 255, 255, 255);
    imagefill($im, 0, 0, $k);
    for ($x = 0; $x < $width; $x++) {
        $color = value2color($x, 0, $width - 1);
        $b = hexdec(substr($color, 0, 2));
        $v = hexdec(substr($color, 2, 2));
        $r = hexdec(substr($color, 4, 2));
        $k = imagecolorallocate($im, $r, $v, $b);
        imageline($im, $x, 0, $x, $height - 1, $k);
    }
    $png = imagepng2string($im);
    imagedestroy($im);
    return $png;
}

/*
Function: imagepng2string
        Convert a PNG image to its string representation

Arguments:
        $image - PNG image

Returns:
        The image as a string

*/
function imagepng2string($image) {
    $contents = ob_get_contents();
    if ($contents !== false) ob_clean(); 
    else ob_start();
    imagepng($image);
    $data = ob_get_contents();
    if ($contents !== false) {
        ob_clean();
        echo $contents;
    } else ob_end_clean();
    return $data;
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
function generate_colored_track($jsonTrack, $idxSerie, $unit, &$minValue, &$maxValue) {
    $track = @json_decode($jsonTrack, true);

    $maxValue = $minValue = $track[$idxSerie][0];

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

    $point = 1;
    $lastColor = value2color($track[$idxSerie][0], $minValue, $maxValue);
    do {
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
                 $lastColor,
                 $track['date']['year'],
                 $track['date']['month'],
                 $track['date']['day'],
                 $track['time']['hour'][$chartIndex],
                 $track['time']['min'][$chartIndex],
                 $track['time']['sec'][$chartIndex]);

        $file .= sprintf("        %010.6f, %010.6f, %05d\n",
                         $track['lon'][$point - 1],
                         $track['lat'][$point - 1],
                         get_track_elevation($track, $point - 1));

        while (1) {
            $chartIndex = $point * ($track['nbChartPt'] - 1) / ($track['nbTrackPt'] - 1);
            $file .= sprintf("        %010.6f, %010.6f, %05d\n",
                             $track['lon'][$point],
                             $track['lat'][$point],
                             get_track_elevation($track, $point));
            $color = value2color($track[$idxSerie][$chartIndex], $minValue, $maxValue);
            $point++;
            if ($color != $lastColor) break;
            if ($point >= $track['nbTrackPt']) break;
        }
        $lastColor = $color;
        $file .= "        </coordinates>\n" .
                 "    </LineString>\n" .
                 "</Placemark>\n";
    } while ($point < $track['nbTrackPt']);

    $file .= sprintf("<Placemark>\n" .
                     "    <name>Deco</name>\n" .
                     "    <Point>\n" .
                     "        <coordinates>\n" .
                     "          %010.6f, %010.6f, %05d\n" .
                     "        </coordinates>\n" .
                     "    </Point>\n" .
                     "</Placemark>\n" .
                     "<Placemark>\n" .
                     "    <name>Atterro</name>\n" .
                     "    <Point>\n" .
                     "        <coordinates>\n" .
                     "          %010.6f, %010.6f, %05d\n" .
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
                    substr(strval($track['date']['year']), -2),
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

    if ($Mm == 0) $Mm = 1;
    if ($xm >= 2 * $Mm / 3)
        $k = '00' . sprintf("%02X",0xFF*3*(1 - $xm / $Mm)) . 'FF';
    elseif ($xm >= $Mm/2)
        $k = '00FF' . sprintf("%02X", 0xFF * (6 * $xm / $Mm - 3));
    elseif ($xm >= $Mm/3)
        $k = sprintf("%02X", 0xFF * (3 - 6 * $xm / $Mm)) . 'FF00';
    else
        $k = 'FF' . sprintf("%02X", 0xFF * 3 * $xm / $Mm) . '00';

   return $k;
}

?>
