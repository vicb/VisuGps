/*
 * GpsPosition.java
 *
 * Created on November 15, 2007, 10:14 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.gps;

import fr.victorb.mobile.utils.Date;
import fr.victorb.mobile.utils.Time;

/**
 *
 * @author Victor
 */
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
