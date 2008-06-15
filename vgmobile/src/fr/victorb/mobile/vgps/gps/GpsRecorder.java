/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.gps;

import fr.victorb.mobile.vgps.controller.Controller;
import java.util.Timer;
import java.util.TimerTask;
import java.util.Vector;

/**
 *
 * @author a0919217
 */
public class GpsRecorder extends TimerTask implements GpsListener {

    private Vector positions = new Vector();
    private Gps gps;   
    private boolean fixValid;
    
    public GpsRecorder(Gps gps) {
        super();
        this.gps = gps;
        gps.addFixValidListener(this);
    }
    
    /**
     * Append recorded position and date to specified arrays
     * @param pos array of positions
     */
    public synchronized void appendRecords(Vector pos) {
        for (int i = 0; i < positions.size(); i++) {
            pos.addElement((GpsPosition)positions.elementAt(i));            
        }
        positions.removeAllElements();
    }    
    
    public void start() {
        Controller controller = Controller.getController();
        positions.removeAllElements();
        fixValid = false;
        new Timer().scheduleAtFixedRate(this, 5000, controller.configuration.getLogInterval() * 1000);
    }
    
    public void stop() {
        cancel();
    }
    
    /**
     * Record GPS positions and dates in an array
     */
    public synchronized void run() {
        if (fixValid) {
            positions.addElement(gps.getPosition().clone());
        }
    }

    public void gpsPositionUpdated(GpsPosition position) {
    }

    public void gpsFixValidUpdated(boolean valid) {
        fixValid = valid;
    }

}
