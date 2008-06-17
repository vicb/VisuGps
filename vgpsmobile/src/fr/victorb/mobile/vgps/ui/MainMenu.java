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

import fr.victorb.mobile.vgps.controller.Controller;
import fr.victorb.mobile.vgps.gps.BluetoothGps;
import fr.victorb.mobile.vgps.gps.Gps;
import fr.victorb.mobile.vgps.gps.GpsListener;
import fr.victorb.mobile.vgps.gps.GpsPosition;
import fr.victorb.mobile.vgps.gps.GpsRecorder;
import fr.victorb.mobile.vgps.gps.GpsSender;
import java.io.IOException;
import javax.microedition.lcdui.Alert;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Image;
import javax.microedition.lcdui.List;

public class MainMenu extends List implements CommandListener, GpsListener {
    private Command cmdExit = new Command("Exit", Command.EXIT, 1);
    private Command cmdSelect = new Command("Select", Command.ITEM, 1);
    
    private Controller controller;
    
    private String gpsName = "-";
    
    private Gps gps = new BluetoothGps();
    
    private static final int RECORD_START = 0;
    private static final int RECORD_STOP = 1;
    
    private int recordState = RECORD_STOP;
    
    private GpsRecorder recorder;
    private GpsSender sender;
       
    /** Creates a new instance of MainMenu
     * @param controller controller
     */
    public MainMenu(Controller controller) {
        super("Menu", List.IMPLICIT);
        this.controller = controller;
        addCommand(cmdExit);
        setSelectCommand(cmdSelect);
        try {
            append("GPS: -", Image.createImage(this.getClass().getResourceAsStream("/res/gps.png")));
            append("Options", Image.createImage(this.getClass().getResourceAsStream("/res/config.png")));
            append("Start", Image.createImage(this.getClass().getResourceAsStream("/res/start.png")));
            append("Fix invalid", Image.createImage(this.getClass().getResourceAsStream("/res/invalid.png")));
            append("About...", Image.createImage(this.getClass().getResourceAsStream("/res/about.png")));
        } catch (IOException ex) {
        }
        
        setCommandListener(this);        
    }

    public void setGpsName(String name) {
        if (name == null || name.equals("")) {
            gpsName = "-";            
        } else {
            gpsName = name;            
        }
        try {
            set(0, "GPS: " + gpsName, Image.createImage(this.getClass().getResourceAsStream("/res/gps.png")));
        } catch (Exception e) {
        }
    }
    
    public void commandAction(Command command, Displayable displayable) {
        if (command == cmdSelect) {
            switch (getSelectedIndex()) {
                case 0:
                    controller.searchDevice();
                    break;
                case 1:
                    if (recordState == RECORD_STOP) {
                        controller.showOptionMenu();
                    } else {
                        Alert alert = new Alert("Warning", "Options can not be changed when tracking is active", null, null);
                        alert.setTimeout(Alert.FOREVER);
                        controller.getDisplay().setCurrent(alert, this);                        
                    }
                    break;
                case 2:
                    if (recordState == RECORD_STOP) {
                        // Start GSP tarcking
                        recordState = RECORD_START;                        
                        try {
                            set(2, "Stop", Image.createImage(this.getClass().getResourceAsStream("/res/start.png")));
                        } catch (IOException ex) {
                        }
                        recorder = new GpsRecorder(gps);
                        sender = new GpsSender(gps, recorder);
                        gps.start(controller.configuration.getGpsUrl());
                        gps.addFixValidListener(this);
                        recorder.start();
                        sender.start();                    
                    } else {
                        sender.stop();
                        recorder.stop();                        
                        gps.stop();                        
                        recordState = RECORD_STOP;
                        try {
                            set(2, "Start", Image.createImage(this.getClass().getResourceAsStream("/res/start.png")));
                        } catch (IOException ex) {
                        }
                        gps.removeFixValidListner(this);                  
                    }
                    break;
                case 4:
                    try {
                        Alert alert = new Alert("VGpsMobile", controller.getVersion() + "\nby Victor Berchet\nwww.victorb.fr", 
                                                Image.createImage(this.getClass().getResourceAsStream("/res/icon_big.png")), null);
                        alert.setTimeout(Alert.FOREVER);
                        controller.getDisplay().setCurrent(alert, this);
                    }catch (IOException ex) {
                    }
                    break;
            }
        } else if (command == cmdExit) {
            controller.exit();
        }
    }

    public void gpsPositionUpdated(GpsPosition position) {
    }

    public void gpsFixValidUpdated(boolean valid) {
        if (valid) {
            try {
                set(3, "Fix valid", Image.createImage(this.getClass().getResourceAsStream("/res/valid.png")));
            } catch (IOException ex) {
            }
        } else {
            try {
                set(3, "Fix invalid", Image.createImage(this.getClass().getResourceAsStream("/res/invalid.png")));
            } catch (IOException ex) {
            }
        }
    }
   
}
