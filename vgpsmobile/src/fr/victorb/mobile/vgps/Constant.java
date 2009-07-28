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

package fr.victorb.mobile.vgps;

public class Constant {
    // VERSION is automaticaly set at application startup (from descriptor)
    public static String VERSION;
    
    //Test    public static final String LOGURL = "http://www.ilpulcino.org/php/mvg_track.php";
    public static final String LOGURL = "http://www.victorb.fr/visugps/php/mvg_track.php";
    public static final String WEATHERURL = "http://www.victorb.fr/visugps/php/mvg_weather.php";
    public static final String SITEURL = "http://www.victorb.fr/visugps/php/mvg_sites.php";
    public static final String GMAPURL = "http://www.victorb.fr/visugps/php/mvg_staticmap.php";
       
    public static final String INTERNALGPS = "Internal Gps";
    public static final String SOCKETGPS = "Socket Gps (WM)";
    
    public static final int AUTOSTARTSPEED = 20;
    public static final int AUTOSTARTTIME = 20;
    public static final int AUTOSTOPSPEED = 10;
    public static final int AUTOSTOPTIME = 90;
    
    public static final String GPSPORT = "1234";
}
