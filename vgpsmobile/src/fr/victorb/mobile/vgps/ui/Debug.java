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
import javax.microedition.lcdui.Alert;
import javax.microedition.lcdui.AlertType;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.List;

public class Debug extends List implements CommandListener {
    private Command cmdExit = new Command("Back", Command.BACK, 1);
    private Command cmdSelect = new Command("Select", Command.ITEM, 1);    

    public Debug() {
        super("Debug", List.IMPLICIT);
        setSelectCommand(cmdSelect);
        addCommand(cmdExit);
        setCommandListener(this);
    }
    
    public void append(String value) {
        if (size() > 50) {
            clear();
        }
        append(value, null);
    }
    
    public void clear() {
        deleteAll();
    }
    
    public void commandAction(Command cmd, Displayable display) {
        Controller controller = Controller.getController();
        if (cmd == cmdSelect) {
            int index = getSelectedIndex();
            Alert alert = new Alert("Log", getString(index), null, AlertType.INFO);
            alert.setTimeout(Alert.FOREVER);
            controller.getDisplay().setCurrent(alert, display);
        } else {
            controller.showMoreMenu();
        }
    }
    

    
    
    
    
    
}
