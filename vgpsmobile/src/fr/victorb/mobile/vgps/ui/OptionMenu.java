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
import fr.victorb.mobile.vgps.config.Configuration;
import fr.victorb.mobile.vgps.controller.Controller;
import fr.victorb.mobile.vgps.gps.GpsType;
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
    private ChoiceGroup gpsChoice;
    private ChoiceGroup autoModeChoice;
    private Controller controller;
    
    private Command cmdOk = new Command("Ok", Command.OK, 1);
    private Command cmdCancel = new Command("Cancel", Command.CANCEL, 1);
    private boolean[] useInternalGps = new boolean[] {true};
    private boolean[] useAutoMode = new boolean[] {true, true};
    
    public OptionMenu() {
        super("Options");
        controller = Controller.getController();
        
        append(idTxt = new TextField("Pilot ID", controller.configuration.getPilotId(), 10, TextField.ANY));
        append(logChoice = new ChoiceGroup("Log/Send Periods", Choice.EXCLUSIVE, new String[] {"5s/5min", "10s/10min", "1min/30min", "10min/30min"}, null));

//#if USE_INTERNAL_GPS        
//#         if (GpsUtil.hasInternalGps()) {
//#             append(gpsChoice = new ChoiceGroup("Gps Type", Choice.EXCLUSIVE, new String[]{"Bluetooth", "Socket (WM)", "Internal"}, null));
//#         } else {
//#             append(gpsChoice = new ChoiceGroup("Gps Type", Choice.EXCLUSIVE, new String[]{"Bluetooth", "Socket (WM)"}, null));
//#         }
//#else
        append(gpsChoice = new ChoiceGroup("Gps Type", Choice.EXCLUSIVE, new String[]{"Bluetooth", "Socket (WM)"}, null));
//#endif        
        
        append(autoModeChoice = new ChoiceGroup("Automatic mode", Choice.MULTIPLE, new String[] {"Use auto start", "Use auto stop"}, null));
        
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

        switch (cfg.getGpsType()) {
            case GpsType.BLUETOOTH:
                gpsChoice.setSelectedIndex(0, true);
                break;
            case GpsType.SOCKET:
                gpsChoice.setSelectedIndex(1, true);
                break;
            default:
                gpsChoice.setSelectedIndex(2, true);
        }
           
        useAutoMode[0] = cfg.getUseAutoStart();
        useAutoMode[1] = cfg.getUseAutoStop();
        autoModeChoice.setSelectedFlags(useAutoMode);
        
    }

    public void commandAction(Command command, Displayable arg1) {
        if (command == cmdOk) {
            Configuration cfg = controller.configuration;
            cfg.setPilotId(idTxt.getString());
            switch (logChoice.getSelectedIndex()) {
                case 0:
                    cfg.setLogInterval(5);
                    cfg.setSendInterval(5);                    
                    break;
                case 1:
                    cfg.setLogInterval(10);
                    cfg.setSendInterval(10);                    
                    break;
                case 2:
                    cfg.setLogInterval(60);
                    cfg.setSendInterval(30);                    
                    break;
                default:
                    cfg.setLogInterval(600);
                    cfg.setSendInterval(30);
            }

            switch (gpsChoice.getSelectedIndex()) {
                case 0:
                    cfg.setGpsType(GpsType.BLUETOOTH);
                    break;
                case 1:
                    cfg.setGpsType(GpsType.SOCKET);
                    break;
                default:
                    cfg.setGpsType(GpsType.INTERNAL);
            }

            autoModeChoice.getSelectedFlags(useAutoMode);
            cfg.setUseAutoStart(useAutoMode[0]);
            cfg.setUseAutoStop(useAutoMode[1]);
            
            controller.saveConfig();
            controller.setGpsName();
            controller.createGps();
        }        
        controller.showMainMenu();
    }
}
