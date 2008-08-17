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
    // Code base on Geoportal reference
    // https://api.ign.fr/geoportail/doc/fr/webmaster/proxy_wfs.html

    // Get the REST call path from the AJAX application
    // Is it a POST or a GET?
    $url = isset($_POST['url']) ? $_POST['url'] : $_GET['url'];

    // Open the Curl session
    $session = curl_init($url);

    // If it's a POST, put the POST data in the body
    if (isset($_POST['url'])) {
        $postvars = '';
        while ($element = current($_POST)) {
            $postvars .= key($_POST).'='.$element.'&';
            next($_POST);
        }
        curl_setopt ($session, CURLOPT_POST, true);
        curl_setopt ($session, CURLOPT_POSTFIELDS, $postvars);
    }

    // Don't return HTTP headers. Do return the contents of the call
    curl_setopt($session, CURLOPT_HEADER, false);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    // Make the call
    $xml = curl_exec($session);

    // The web service returns XML. Set the Content-Type appropriately
    header("Content-Type: text/xml");

    echo $xml;
    curl_close($session);

?>
