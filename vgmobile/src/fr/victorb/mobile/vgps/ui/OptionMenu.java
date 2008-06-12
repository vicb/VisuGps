/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
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
