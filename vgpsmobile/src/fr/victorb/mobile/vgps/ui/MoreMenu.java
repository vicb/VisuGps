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

import fr.victorb.mobile.utils.GpsUtil;
import fr.victorb.mobile.vgps.controller.Controller;
import java.io.IOException;
import javax.microedition.lcdui.Alert;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Image;
import javax.microedition.lcdui.List;

public class MoreMenu extends List implements CommandListener {
    private Command cmdBack = new Command("Back", Command.BACK, 1);
    private Command cmdSelect = new Command("Select", Command.ITEM, 1);
    
    private Controller controller;
             
    /** Creates a new instance of MainMenu
     */
    public MoreMenu() {
        super("More Menu", List.IMPLICIT);
        controller = Controller.getController();
        setSelectCommand(cmdSelect);
        addCommand(cmdBack);        
        try {
            append("Test data transfer", Image.createImage(this.getClass().getResourceAsStream("/res/phone.png")));
            append("Weather", Image.createImage(this.getClass().getResourceAsStream("/res/weather.png")));
            append("Flying sites", Image.createImage(this.getClass().getResourceAsStream("/res/map.png")));
            append("Where am I ?", Image.createImage(this.getClass().getResourceAsStream("/res/map.png")));
            append("About...", Image.createImage(this.getClass().getResourceAsStream("/res/about.png")));
//#if DEBUG
//#             append("Debug", Image.createImage(this.getClass().getResourceAsStream("/res/bug.png")));
//#endif        

        } catch (IOException ex) {
        }
        
                       
        setCommandListener(this);        
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
                    GpsUtil.testDataTransfer();
                    break;
                case 1:
                    controller.showWeather();
                    break;
                case 2:
                    controller.showSites();
                    break;
                case 3:
                    controller.showWhereAmI();
                    break;
                case 4:
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
                case 5:
                    controller.showDebug();
            }
        } else if (command == cmdBack) {
            controller.showMainMenu();
        }
    }
   
}
