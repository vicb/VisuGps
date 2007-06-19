<?php
/*
Script: vg_tilesmodis.php
        Create STRM tiles for google maps

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

    require('vg_cfg.php');
    require('vg_cache.php');

    if (isset($_GET['x']) &&
        isset($_GET['y']) &&
        isset($_GET['z']) &&
        isset($_GET['date'])) {

        $x = $_GET['x'];
        $y = $_GET['y'];
        $z = $_GET['z'];
        $date = $_GET['date'];

        $modis = new Modis($x, $y, $z, $date);
        header('Content-Type: image/png');
        echo $modis->getTile();
    } else {
        header('Content-Type: text/plain');
        echo('invalid URL');
    }


    class Modis {

        private $latLimits = array(33.3434, 45.0346, 56.7258);
        private $lonLimits = array(-12.9107, 1.7154, 16.3414, 30.9675, 45.5936, 60.2196);
        private $x, $y, $z, $date;
        private $proj;

        // Public functions
        public function __construct($x, $y, $z, $date) {
            $this->x = $x;
            $this->y = $y;
            $this->z = $z;
            $this->date = $date;
            $this->proj = new Euclide($z, 256);
        }

        public function getTile() {
            $cache = new Cache(CACHE_BASE_FOLDER . CACHE_FOLDER_MODIST, CACHE_NB_MODIST, 9);

            $url = "?x=$this->x&y=$this->y&z=$this->z&date=$this->date";

            if (!$cache->get($data, $url)) {
                $data = $this->makeTile();
                $cache->set($data, $url);
            }

            return $data;

        }

        // Private functions
        private function makeTile() {
            set_time_limit(0);
            $dstImg = imagecreatetruecolor(256, 256);

            $lats[0] = $latB = $this->proj->Lat($this->y + 1);
            $latT = $this->proj->Lat($this->y);

            foreach ($this->latLimits as $lat) {
                if (($lat > $latB) &&
                    ($lat < $latT)) {
                    $lats[] = $lat;
                }
            }

            $lats[] = $latT;

            $lons[0] = $lonL = $this->proj->Lon($this->x);
            $lonR = $this->proj->Lon($this->x + 1);

            foreach ($this->lonLimits as $lon) {
                if (($lon > $lonL) &&
                    ($lon < $lonR)) {
                    $lons[] = $lon;
                }
            }

            $lons[] = $lonR;

            for ($x = 0; $x < count($lons) - 1; $x++) {
                for ($y = 0; $y < count($lats) - 1; $y++) {
                    $this->makeBlock($dstImg, $lats, $y, $lons, $x);
                }
            }

            return PngToString($dstImg);

        }

        private function makeBlock(&$dstImg, $lats, $latNum, $lons, $lonNum) {
            $latDB = $lats[$latNum];
            $latDT = $lats[$latNum + 1];
            $lonDL = $lons[$lonNum];
            $lonDR = $lons[$lonNum + 1];

            $inLon = false;
            $inLat = false;

            for ($lonIdx = 0; $lonIdx < count($this->lonLimits) - 1; $lonIdx++) {
                if (($lonDL >= $this->lonLimits[$lonIdx]) &&
                    ($lonDL < $this->lonLimits[$lonIdx + 1])) {
                    $inLon = true;
                    break;
                }
            }

            for ($latIdx = 0; $latIdx < count($this->latLimits) - 1; $latIdx++) {
                if (($latDT > $this->latLimits[$latIdx]) &&
                    ($latDT <= $this->latLimits[$latIdx + 1])) {
                    $inLat = true;
                    break;
                }
            }

            if (!$inLon || !$inLat) return;

            $srcImg = $this->getSrcTile($latIdx, $lonIdx);

            if (strlen($srcImg) < 20000) return;

            $srcImg = imagecreatefromstring($srcImg);

            $lonSL = $this->lonLimits[$lonIdx];
            $latST = $this->latLimits[$latIdx + 1];

            $wImg = imagesx($srcImg);
            $hImg = imagesy($srcImg);

            $wSDeg = $this->lonLimits[$lonIdx + 1] - $this->lonLimits[$lonIdx];
            $hSDeg = $this->latLimits[$latIdx + 1] - $this->latLimits[$latIdx];

            $xDL = (int)$this->proj->X($lonDL) % 256;
            $xDR = (int)($this->proj->X($lonDR) - 1) % 256;

            $yDT = (int)$this->proj->Y($latDT) % 256;
            $yDB = (int)($this->proj->Y($latDB) - 1) % 256;

            $xSL = (int)(($lonDL - $lonSL) * $wImg / $wSDeg);

            $yST = (int)(($latST - $latDT) * $hImg / $hSDeg);

            $wSPx = (int)(($lonDR - $lonDL) * $wImg / $wSDeg);
            $hSPx = (int)(($latDT - $latDB) * $hImg / $hSDeg);

            imagecopyresampled($dstImg, $srcImg,
                               $xDL, $yDT,
                               $xSL, $yST,
                               ($xDR - $xDL + 1), ($yDB - $yDT + 1),
                               $wSPx, $hSPx);

            return;

        }

        private function getSrcUrl($latIdx, $lonIdx) {
            $x = 1 + $lonIdx;
            $y = 3 - $latIdx;
            $date = $this->date;

            $url = "http://rapidfire.sci.gsfc.nasa.gov/subsets/Europe_${y}_0${x}/" .
                   "${date}/Europe_${y}_0${x}.${date}.terra.500m.jpg";

            return $url;
        }

        private function getSrcTile($latIdx, $lonIdx) {
            $cache = new Cache(CACHE_BASE_FOLDER . CACHE_FOLDER_MODISS, CACHE_NB_MODISS);

            $url = $this->getSrcUrl($latIdx, $lonIdx);

            if (!$cache->get($data, $url)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_FAILONERROR, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                $data = curl_exec($ch);
                curl_close($ch);

                $cache->set($data, $url);
            }

            return $data;
        }
    }

    class Euclide {
        private $nbTiles;
        private $mapSize;

        public function __construct($zoom, $tileSize) {
            $this->nbTiles = pow(2, $zoom);
            $this->mapSize = $tileSize * $this->nbTiles;
        }

        public function X($lonDeg) {
            return ($lonDeg + 180) * $this->mapSize / 360;
        }

        public function Lon($x) {
            return $x * 360 / $this->nbTiles - 180;
        }

        public function Y($latDeg){
            return (90 - $latDeg) * $this->mapSize / 180;
        }

        public function Lat($y) {
            return 90 - (180 * $y) / $this->nbTiles;
        }

    }

    // Image function
    function PngToString($image)
    {
        $contents = ob_get_contents();
        
        if ($contents !== false) {
            ob_clean();
        } else {
            ob_start();
        }
        
        imagepng($image);
        $data = ob_get_contents();
        
        if ($contents !== false) {
            ob_clean();
            echo $contents;
        } else {
            ob_end_clean();
        }
        
        return $data;
    }

?>
