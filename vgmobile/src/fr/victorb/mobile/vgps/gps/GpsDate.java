/*
 * GpsDate.java
 *
 * Created on November 15, 2007, 10:14 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.gps;

import fr.victorb.mobile.utils.Time;
import fr.victorb.mobile.utils.Date;

/**
 *
 * @author Victor
 */
public class GpsDate {
    public Date date = new Date();
    public Time time = new Time();
    
    public GpsDate clone() {
        GpsDate clone = new GpsDate();
        clone.date.day = date.day;
        clone.date.month = date.month;
        clone.date.year = date.year;
        clone.time.hour = time.hour;
        clone.time.minute = time.minute;
        clone.time.second = time.second;
        return clone;
    }
}