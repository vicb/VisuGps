<?php
/*
Script: mvg_static_map.php
        Display a static map using google maps API

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

if (isset($_GET['s'])) $p['size'] = $_GET['s'];
if (isset($_GET['m'])) $p['markers'] = $_GET['m'];
if (isset($_GET['z'])) $p['zoom'] = $_GET['z'];

$p['key'] = GMAPS_KEY;
$p['format'] = 'jpg';

header('Content-Type: image/jpg');
echo getMap($p);

function getMap($params) {
    $url = sprintf('http://maps.google.com/staticmap?%s', http_build_query($params));
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}
?>
