/*
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

package fr.victorb.mobile.vgps.ui;

import fr.victorb.mobile.vgps.Constant;

public class MapViewer extends ImageViewer {

    /**
     * Display a map with 2 markers
     * @param latSite Site marker latitude
     * @param lngSite Site marker longitude
     * @param lat Other marker latitude
     * @param lng Other marker longitude
     */
    public MapViewer(float latSite, float lngSite, float lat, float lng) {
        super("");
        int w = Math.min(getWidth() * 2, 512);
        int h = Math.min(getHeight() * 2, 512);
        url = Constant.GMAPURL + "?size=" + w + "x"  + h + "&" +
              "maptype=mobile&markers=" + latSite + "," + lngSite +",smallgreen|" +
              lat + "," + lng + ",smallblue&" +
              "key=" + Constant.GMAPKEY;           
    }

    /**
     * Display a map with one marker
     * @param lat Marker latitude
     * @param lng Marker longitude
     * @param zoom Zoom level (as understood by Google Maps APIs)
     */
    public MapViewer(float lat, float lng, int zoom) {
        super("");
        int w = Math.min(getWidth() * 2, 512);
        int h = Math.min(getHeight() * 2, 512);
        url = Constant.GMAPURL + "?size=" + w + "x"  + h + "&" +
              "maptype=mobile&markers=" + lat + "," + lng +",smallgreen&" +
              "zoom=" + zoom +
              "&key=" + Constant.GMAPKEY;           
    }
}
