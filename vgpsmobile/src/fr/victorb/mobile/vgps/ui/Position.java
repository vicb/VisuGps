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

import fr.victorb.mobile.utils.Converter;
import fr.victorb.mobile.vgps.controller.Controller;
import fr.victorb.mobile.vgps.controller.RecordState;
import fr.victorb.mobile.vgps.gps.Gps;
import fr.victorb.mobile.vgps.gps.GpsListener;
import fr.victorb.mobile.vgps.gps.GpsPosition;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.List;


public class Position extends List implements CommandListener, GpsListener {
    private Command cmdBack = new Command("Back", Command.BACK, 1);    
    private Controller controller;
    private Gps gps;
             
    public Position() {
        super("Position", List.IMPLICIT);
        controller = Controller.getController();
        addCommand(cmdBack);        
        append("Lat: ", null);
        append("Lon: ", null);
        append("Elev: ", null);
        append("Speed: ", null);     
        append("Time: ", null);
        append("Fix: Invalid", null);                       
        setCommandListener(this);        
    }
    
    public void init(Gps gps) {
        this.gps = gps;
        gps.addFixValidListener(this);
        gps.addPositionListener(this); 
        if (controller.getRecordState() == RecordState.STOP) {
                gps.start(controller.configuration.getGpsUrl());
        }        
    }
   
    /**
     * Handle user input
     * @param command
     * @param displayable
     */
    public void commandAction(Command command, Displayable displayable) {
        if  (command == cmdBack) {
            gps.removeFixValidListner(this);
            gps.removePositionListener(this);
            if (controller.getRecordState() == RecordState.STOP) {
                    // Stop the GPS if we has been started
                    gps.stop();
            }   
            controller.showMoreMenu();
        }        
    }

    public void gpsPositionUpdated(GpsPosition position) {
        set(0, "lat: " + String.valueOf(Converter.degMinToDeg(position.latitude)), null);
        set(1, "lon: " + String.valueOf(Converter.degMinToDeg(position.longitude)), null);
        set(2, "elev: " + String.valueOf(position.elevation) + "m", null);
        set(3, "Speed: " + String.valueOf(position.speed) + "km/h", null);
        set(4, "Time: " + String.valueOf(position.time.hour) + ":" +
                          String.valueOf(position.time.minute) + ":" +
                          String.valueOf(position.time.second), null);
    }

    public void gpsFixValidUpdated(boolean valid) {
        String msg = (valid?"valid":"invalid");
        set(5, "Fix: " + msg, null);
    }   
}
