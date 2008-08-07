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

//#if USE_INTERNAL_GPS
//# import fr.victorb.mobile.utils.GpsUtil;
//#endif
import fr.victorb.mobile.vgps.Constant;
import fr.victorb.mobile.vgps.controller.Controller;
import fr.victorb.mobile.vgps.controller.RecordState;
import fr.victorb.mobile.vgps.gps.GpsListener;
import fr.victorb.mobile.vgps.gps.GpsPosition;
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
    
    private Image imgValid;
    private Image imgInvalid;
          
    /** Creates a new instance of MainMenu
     */
    public MainMenu() {
        super("Menu", List.IMPLICIT);
        controller = Controller.getController();
        setSelectCommand(cmdSelect);
        addCommand(cmdExit);        
        try {
            imgValid = Image.createImage(this.getClass().getResourceAsStream("/res/valid.png"));
            imgInvalid = Image.createImage(this.getClass().getResourceAsStream("/res/invalid.png"));
            append("GPS: -", Image.createImage(this.getClass().getResourceAsStream("/res/gps.png")));
            append("Options", Image.createImage(this.getClass().getResourceAsStream("/res/config.png")));
            append("Start", Image.createImage(this.getClass().getResourceAsStream("/res/start.png")));
            append("Fix invalid", imgInvalid);
            append("Weather", Image.createImage(this.getClass().getResourceAsStream("/res/weather.png")));
            append("Flying sites", Image.createImage(this.getClass().getResourceAsStream("/res/map.png")));
            append("Where am I ?", Image.createImage(this.getClass().getResourceAsStream("/res/map.png")));
            append("Minimize", Image.createImage(this.getClass().getResourceAsStream("/res/minimize.png")));
            append("About...", Image.createImage(this.getClass().getResourceAsStream("/res/about.png")));
        } catch (IOException ex) {
        }
        
//#if DEBUG
//#         append("Debug", null);
//#endif        
        
        controller.logAppend(controller.configuration.getGpsName());
        controller.logAppend(controller.configuration.getGpsUrl());
                
        setCommandListener(this);        
    }

    /**
     * Set the name of the GPS device
     * @param name GPS name
     */
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
    
    /**
     * Handle user input
     * @param command
     * @param displayable
     */
    public void commandAction(Command command, Displayable displayable) {
        if (command == cmdSelect) {
            switch (getSelectedIndex()) {
                case 0:
//#if USE_INTERNAL_GPS
//#                     if (controller.getRecordState() == RecordState.STOP && !controller.configuration.getUseInternalGps()) {
//#                         controller.searchDevice();
//#                     }
//#else
                    if (controller.getRecordState() == RecordState.STOP) {                    
                        controller.searchDevice();
                    }
//#endif
                    break;
                case 1:
                    if (controller.getRecordState() == RecordState.STOP) {
                        controller.showOptionMenu();
                    } else {
                        Alert alert = new Alert("Warning", "Options can not be changed when tracking is active", null, null);
                        alert.setTimeout(Alert.FOREVER);
                        controller.getDisplay().setCurrent(alert, this);                        
                    }
                    break;
                case 2:
                    try {
                        if (controller.getRecordState() == RecordState.STOP) {
                            controller.requestStart();
                            set(2, "Stop", Image.createImage(this.getClass().getResourceAsStream("/res/start.png")));
                            controller.getGps().addFixValidListener(this);
                        } else {
                            requestStop();
                        }
                    } catch (IOException ex) {
                    }                            
                    break;
                case 4:
                    controller.showWeather();
                    break;
                case 5:
                    controller.showSites();
                    break;
                case 6:
                    controller.showWhereAmI();
                    break;
                case 7:
                    controller.getDisplay().setCurrent(null);
                    break;
                case 8:
                    try {
                        Alert alert = new Alert("VGpsMobile", controller.getVersion() + 
                                                " - by Victor Berchet - www.victorb.fr - " + 
                                                "METAR by geonames.org - " +
                                                "Flying sites by paraglidingearth.com", 
                                                Image.createImage(this.getClass().getResourceAsStream("/res/icon_big.png")), null);
                        alert.setTimeout(Alert.FOREVER);
                        controller.getDisplay().setCurrent(alert, this);
                    }catch (IOException ex) {
                    }
                    break;
                case 9:
                    controller.showDebug();
            }
        } else if (command == cmdExit) {
            controller.exit();
        }
    }

    /**
     * Called when a new position is available
     * @param position
     */
    public void gpsPositionUpdated(GpsPosition position) {
    }

    /**
     * Update the fix item based on its availability
     * @param valid
     */
    public void gpsFixValidUpdated(boolean valid) {
        if (valid) {
            set(3, "Fix valid", imgValid);
        } else {
            set(3, "Fix invalid", imgInvalid);
        }
    }
    
    public void requestStop() throws IOException {
        controller.requestStop();
        set(2, "Start", Image.createImage(this.getClass().getResourceAsStream("/res/start.png")));
        controller.getGps().removeFixValidListner(this);                 
    }
   
}
