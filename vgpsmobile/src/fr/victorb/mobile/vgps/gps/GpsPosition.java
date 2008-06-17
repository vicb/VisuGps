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

package fr.victorb.mobile.vgps.gps;

import fr.victorb.mobile.utils.Date;
import fr.victorb.mobile.utils.Time;

public class GpsPosition {
        public float latitude;
        public float longitude;
        public int elevation;
        public Time time = new Time();
        public Date date = new Date();
        
        public GpsPosition clone() {
            GpsPosition clone = new GpsPosition();
            clone.latitude = latitude;
            clone.longitude = longitude;
            clone.elevation = elevation;
            clone.time = time.clone();
            clone.date = date.clone();
            return clone;            
        }
}
