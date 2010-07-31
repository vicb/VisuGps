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

Copyright (c) 2007-2008 Victor Berchet, <http://www.victorb.fr>

*/
    if (isset($_GET['url'])) {
        $url = $_GET['url'];
    } else {
        exit;
    }

    // Open the Curl session
    $session = curl_init($url);
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
    $xml = curl_exec($session);

    header("Content-Type: text/xml");
    echo $xml;
    curl_close($session);

?>
