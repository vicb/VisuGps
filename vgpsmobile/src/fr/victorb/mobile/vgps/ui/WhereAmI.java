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
import javax.microedition.lcdui.Form;
import javax.microedition.lcdui.StringItem;

public class WhereAmI extends Form implements CommandListener {
    private Command cmdExit = new Command("Exit", Command.EXIT, 1);
    private Controller controller;
    private Helper helper;
    private Gps gps;  
    
    public WhereAmI() {
        super("Where am I ?");
        controller = Controller.getController();
        addCommand(cmdExit);
        setCommandListener(this);
    }
    
    public void start() {        
        new Thread(helper = new Helper()).start();
    }
    
    
    private class Helper implements GpsListener, Runnable {
        private boolean fixValid = false;
        
        public void run() {
            deleteAll();
            append(new StringItem("", "Waiting for a valid GPS fix"));
            gps = controller.getGps();
            if (controller.getRecordState() == RecordState.STOP) {
                // Start the GPS to get the location
                gps.start(controller.configuration.getGpsUrl());
            }
            gps.addFixValidListener(this);
            gps.addPositionListener(this);                                 
        }              
        
        public void gpsPositionUpdated(GpsPosition position) {
            if (fixValid) {
                // Fetch weather info only once
                gps.removePositionListener(this);
                gps.removeFixValidListner(this);
                controller.viewMap(Converter.degMinToDeg(position.latitude), 
                                   Converter.degMinToDeg(position.longitude),
                                   12, true);
            }
        }
        
        public void gpsFixValidUpdated(boolean valid) {
            fixValid = valid;
        }
        
    }
    
    public void commandAction(Command command, Displayable display) {
        if (controller.getRecordState() == RecordState.STOP) {
            // Stop the GPS if we started it
            gps.stop();
        }
        gps.removePositionListener(helper);
        gps.removeFixValidListner(helper);            
        controller.showMainMenu();
    }

}
