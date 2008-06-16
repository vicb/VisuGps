/*
 * MainMenu.java
 *
 * Created on November 15, 2007, 5:57 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.ui;

import fr.victorb.mobile.vgps.controller.Controller;
import fr.victorb.mobile.vgps.gps.BluetoothGps;
import fr.victorb.mobile.vgps.gps.Gps;
import fr.victorb.mobile.vgps.gps.GpsListener;
import fr.victorb.mobile.vgps.gps.GpsPosition;
import fr.victorb.mobile.vgps.gps.GpsRecorder;
import fr.victorb.mobile.vgps.gps.GpsSender;
import fr.victorb.mobile.vgps.rmsfile.RmsFile;
import java.io.IOException;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Image;
import javax.microedition.lcdui.List;

/**
 *
 * @author a0919217
 */
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
        } catch (IOException ex) {
        }
        append("Fix invalid", null);
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
                    controller.showOptionMenu();
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
            }
        } else if (command == cmdExit) {
            controller.exit();
        }
    }

    public void gpsPositionUpdated(GpsPosition position) {
    }

    public void gpsFixValidUpdated(boolean valid) {
        if (valid) {
            set(3, "Fix valid", null);
        } else {
            set(3, "Fix invalid", null);
        }
    }
   
}
