/*
 * IGps.java
 *
 * Created on November 15, 2007, 6:27 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.gps;

import java.util.Vector;

/**
 *
 * @author a0919217
 */
public abstract class Gps {
    private Vector positionListeners = new Vector();
    private Vector fixValidListeners = new Vector();
       
    public boolean start(String config){
        return false;
    }
    
    public void stop(){        
    }
       
    public GpsPosition getPosition() {
        return new GpsPosition();
    }
    
    public GpsDate getDate() {
        return new GpsDate();
    }
    
    public void addPositionListener(GpsListener listener){
        if (positionListeners.indexOf(listener) == -1) {
            positionListeners.addElement(listener);
        }        
    }
    
    public boolean removePositionListener(GpsListener listener){        
        return positionListeners.removeElement(listener);
    }
    
    public void addFixValidListener(GpsListener listener) {
        if (fixValidListeners.indexOf(listener) == -1) {
            fixValidListeners.addElement(listener);
        }        
    }
    
    public boolean removeFixValidListner(GpsListener listener) {
        return fixValidListeners.removeElement(listener);
    }
    
    protected void updatePosition(GpsPosition position) {
        for (int i = 0; i < positionListeners.size(); i++) {
            ((GpsListener)positionListeners.elementAt(i)).gpsPositionUpdated(position);
        }
    }
    
    protected void updatefixValid(boolean valid) {
        for (int i = 0; i < fixValidListeners.size(); i++) {
            ((GpsListener)fixValidListeners.elementAt(i)).gpsFixValidUpdated(valid);
        }
    }
}
