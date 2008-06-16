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
import javax.microedition.lcdui.Choice;
import javax.microedition.lcdui.ChoiceGroup;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Form;
import javax.microedition.lcdui.TextField;

/**
 *
 * @author a0919217
 */
public class OptionMenu extends Form implements CommandListener {
    
    private TextField idTxt;
    private TextField urlTxt;    
    private ChoiceGroup logChoice;
    private ChoiceGroup sendChoice;
    private Controller controller;
    
    private Command cmdOk = new Command("Ok", Command.OK, 1);
    private Command cmdCancel = new Command("Cancel", Command.CANCEL, 1);
    
    public OptionMenu(Controller controller) {
        super("Options");
        this.controller = controller;
        
        append(idTxt = new TextField("Pilot ID", controller.configuration.getPilotId(), 10, TextField.ANY));
        append(urlTxt = new TextField("Log URL", controller.configuration.getLogUrl(), 50, TextField.URL));
        append(logChoice = new ChoiceGroup("Log Interval (sec)", Choice.EXCLUSIVE, new String[] {"10", "60", "600"}, null));
        append(sendChoice = new ChoiceGroup("Track Interval (min)", Choice.EXCLUSIVE, new String[] {"10", "30", "60"}, null));       

        addCommand(cmdOk);
        addCommand(cmdCancel);
        
        setCommandListener(this);
    }
    
    public void init() {
        idTxt.setString(controller.configuration.getPilotId());
        urlTxt.setString(controller.configuration.getLogUrl());
        switch (controller.configuration.getLogInterval()) {
            case 10:
                logChoice.setSelectedIndex(0, true);
                break;
            case 60:
                logChoice.setSelectedIndex(1, true);
                break;
            default:
                logChoice.setSelectedIndex(2, true);
        }

        switch (controller.configuration.getSendInterval()) {
            case 10:
                sendChoice.setSelectedIndex(0, true);
                break;
            case 30:
                sendChoice.setSelectedIndex(1, true);
                break;
            default:
                sendChoice.setSelectedIndex(2, true);
        }
    }

    public void commandAction(Command command, Displayable arg1) {
        if (command == cmdOk) {
            controller.configuration.setPilotId(idTxt.getString());
            controller.configuration.setLogUrl(urlTxt.getString());
            switch (logChoice.getSelectedIndex()) {
                case 0:
                    controller.configuration.setLogInterval(10);
                    break;
                case 1:
                    controller.configuration.setLogInterval(60);
                    break;
                default:
                    controller.configuration.setLogInterval(600);
            }

            switch (sendChoice.getSelectedIndex()) {
                case 0:
                    controller.configuration.setSendInterval(10);
                    break;
                case 1:
                    controller.configuration.setSendInterval(30);
                    break;
                default:
                    controller.configuration.setSendInterval(60);
            } 
            
            controller.saveConfig();    
        }        
        controller.showMainMenu();
    }
}
