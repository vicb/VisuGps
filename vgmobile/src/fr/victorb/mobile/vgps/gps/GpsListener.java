/*
 * GpsListener.java
 *
 * Created on November 15, 2007, 9:22 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.gps;

/**
 *
 * @author Victor
 */
public interface GpsListener {
    public void gpsPositionUpdated(GpsPosition position);
    public void gpsFixValidUpdated(boolean valid);    
}
