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
import fr.victorb.mobile.vgps.Constant;
import fr.victorb.mobile.vgps.config.Configuration;
import fr.victorb.mobile.vgps.controller.Controller;
import javax.microedition.lcdui.Choice;
import javax.microedition.lcdui.ChoiceGroup;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Form;
import javax.microedition.lcdui.TextField;

public class OptionMenu extends Form implements CommandListener {
    
    private TextField idTxt;
    private ChoiceGroup logChoice;
    private ChoiceGroup sendChoice;
    private ChoiceGroup gpsChoice;
    private ChoiceGroup autoChoice;
    private Controller controller;
    
    private Command cmdOk = new Command("Ok", Command.OK, 1);
    private Command cmdCancel = new Command("Cancel", Command.CANCEL, 1);
    private boolean[] useInternalGps = new boolean[] {true};
    private boolean[] useAutoMode = new boolean[] {true};
    
    public OptionMenu() {
        super("Options");
        controller = Controller.getController();
        
        append(idTxt = new TextField("Pilot ID", controller.configuration.getPilotId(), 10, TextField.ANY));
        append(logChoice = new ChoiceGroup("Log Interval (sec)", Choice.EXCLUSIVE, new String[] {"5", "10", "60", "600"}, null));
        append(sendChoice = new ChoiceGroup("Track Interval (min)", Choice.EXCLUSIVE, new String[] {"5", "10", "30", "60"}, null));       

//#if USE_INTERNAL_GPS        
//#         if (GpsUtil.hasInternalGps()) {
//#             append(gpsChoice = new ChoiceGroup("Gps", Choice.MULTIPLE, new String[]{"Use internal Gps"}, null));
//#         }
//#endif        
        
        append(autoChoice = new ChoiceGroup("Start", Choice.MULTIPLE, new String[] {"Use automatic mode"}, null));
        
        addCommand(cmdOk);
        addCommand(cmdCancel);
        
        setCommandListener(this);
    }
    
    public void init() {
        Configuration cfg = controller.configuration;
        idTxt.setString(cfg.getPilotId());
        switch (cfg.getLogInterval()) {
            case 5:
                logChoice.setSelectedIndex(0, true);
                break;
            case 10:
                logChoice.setSelectedIndex(1, true);
                break;
            case 60:
                logChoice.setSelectedIndex(2, true);
                break;
            default:
                logChoice.setSelectedIndex(3, true);
        }

        switch (cfg.getSendInterval()) {
            case 5:
                sendChoice.setSelectedIndex(0, true);
                break;
            case 10:
                sendChoice.setSelectedIndex(1, true);
                break;
            case 30:
                sendChoice.setSelectedIndex(2, true);
                break;
            default:
                sendChoice.setSelectedIndex(3, true);
        }

//#if USE_INTERNAL_GPS        
//#         if (GpsUtil.hasInternalGps()) {
//#             useInternalGps[0] = cfg.getUseInternalGps();
//#             gpsChoice.setSelectedFlags(useInternalGps);
//#         }
//#endif
           
        useAutoMode[0] = cfg.getUseAutoMode();
        autoChoice.setSelectedFlags(useAutoMode);
        
    }

    public void commandAction(Command command, Displayable arg1) {
        if (command == cmdOk) {
            Configuration cfg = controller.configuration;
            cfg.setPilotId(idTxt.getString());
            switch (logChoice.getSelectedIndex()) {
                case 0:
                    cfg.setLogInterval(5);
                    break;
                case 1:
                    cfg.setLogInterval(10);
                    break;
                case 2:
                    cfg.setLogInterval(60);
                    break;
                default:
                    cfg.setLogInterval(600);
            }

            switch (sendChoice.getSelectedIndex()) {
                case 0:
                    cfg.setSendInterval(5);
                    break;
                case 1:
                    cfg.setSendInterval(10);
                    break;
                case 2:
                    cfg.setSendInterval(30);
                    break;
                default:
                    cfg.setSendInterval(60);
            } 

//#if USE_INTERNAL_GPS            
//#             if (GpsUtil.hasInternalGps()) {
//#                 gpsChoice.getSelectedFlags(useInternalGps);
//#                 if (useInternalGps[0]) {
//#                     cfg.setUseInternalGps(true);
//#                     controller.setGpsName(Constant.INTERNALGPS);
//#                 } else {
//#                     cfg.setUseInternalGps(false);
//#                     controller.setGpsName(controller.configuration.getGpsName());                    
//#                 }            
//#             }
//#endif

            autoChoice.getSelectedFlags(useAutoMode);
            cfg.setUseAutoMode(useAutoMode[0]);
            
            controller.saveConfig();              
        }        
        controller.showMainMenu();
    }
}
