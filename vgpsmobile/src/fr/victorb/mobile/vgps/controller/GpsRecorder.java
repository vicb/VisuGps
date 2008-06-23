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

package fr.victorb.mobile.vgps.controller;

import fr.victorb.mobile.vgps.gps.*;
import java.util.Timer;
import java.util.TimerTask;
import java.util.Vector;

public class GpsRecorder implements GpsListener {

    private Vector positions = new Vector();
    private Gps gps;   
    private boolean fixValid;
    Timer timer;
    
    /**
     * Constructor
     * @param gps GPS to be used by the recorder
     */
    public GpsRecorder(Gps gps) {
        super();
        this.gps = gps;
        gps.addFixValidListener(this);
    }
    
    /**
     * Append recorded position to the specified array
     * @param pos array of positions
     */
    public synchronized void appendRecords(Vector pos) {
        for (int i = 0; i < positions.size(); i++) {
            pos.addElement((GpsPosition)positions.elementAt(i));            
        }
        positions.removeAllElements();
    }    
    
    /**
     * Start recording GPS position
     */
    public void start() {
        Controller controller = Controller.getController();
        positions.removeAllElements();
        fixValid = false;
        timer = new Timer();
        timer.scheduleAtFixedRate(new Helper(), 5000, controller.configuration.getLogInterval() * 1000);
    }
    
    /**
     * Stop recording gps position
     */
    public void stop() {
        timer.cancel();
    }
    
    /**
     * Called when the gps position has been updated
     * @param position GPS position
     */
    public void gpsPositionUpdated(GpsPosition position) {
    }

    /**
     * Called for each fix giving its status
     * @param valid
     */
    public void gpsFixValidUpdated(boolean valid) {
        fixValid = valid;
    }
    
    private class Helper extends TimerTask {
    /**
     * Record GPS positions and dates in an array
     */
        public synchronized void run() {
            if (fixValid) {
                positions.addElement(gps.getPosition().clone());
            }
        }        
    }

}
