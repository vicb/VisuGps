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
