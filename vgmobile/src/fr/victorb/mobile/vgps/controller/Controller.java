/*
 * Controller.java
 *
 * Created on October 30, 2007, 8:49 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.controller;

import fr.victorb.mobile.vgps.ui.MainMenu;
import fr.victorb.mobile.vgps.bluetooth.BluetoothFinder;
import fr.victorb.mobile.vgps.bluetooth.BluetoothFinderListener;
import fr.victorb.mobile.vgps.config.Configuration;
import fr.victorb.mobile.vgps.rmsfile.RmsFile;
import fr.victorb.mobile.vgps.ui.OptionMenu;
import javax.bluetooth.UUID;
import javax.microedition.lcdui.Display;
import javax.microedition.midlet.MIDlet;

/**
 *
 * @author Victor
 */
public class Controller implements BluetoothFinderListener {    
    private static Controller controller;
    private Display display;
    private boolean paused = false;
    private MIDlet midlet;
    public Configuration configuration = new Configuration();
    private static final String CONFIG_FILE = "config.ini";    
    
    private MainMenu menu;
    private OptionMenu options;
    
    /** Creates a new instance of Controller */
    private Controller() {
        menu = new MainMenu(this);
        options = new OptionMenu(this);
    }
    
    static synchronized public Controller getController() {
        if (controller == null) {
            controller = new Controller();
        }
        return controller;
    }
    
    public void start(MIDlet midlet) {        
        if (paused) {
            
        } else {
            this.midlet = midlet;
            display = Display.getDisplay(midlet);
            try {                
                RmsFile.unserialize(CONFIG_FILE, configuration);
            } catch (Exception e) {
                configuration = new Configuration();
            }                      
        } 

        menu.setGpsName(configuration.getGpsName());
        display.setCurrent(menu);
    }
    
    public void pause() {
        paused = true;
    }

    public void exit() {
        midlet.notifyDestroyed();
    }
    
    public void searchDevice() {
        BluetoothFinder btFinder = new BluetoothFinder(this);;
        display.setCurrent(btFinder);
        btFinder.startSearch(new UUID[] {new UUID(0x1101)});        
    }
    
    public void deviceSearchCompleted(int status, String deviceName, String deviceUrl) {
        if (status == BluetoothFinderListener.DEVICE_FOUND) {
            if (!deviceUrl.equals(configuration.getGpsUrl())) {
                configuration.setGpsUrl(deviceUrl);
                configuration.setGpsName(deviceName);
                menu.setGpsName(deviceName);
                saveConfig();
                System.out.println(deviceUrl);
            }                        
        }
        showMainMenu();
    }

    public void showMainMenu() {
        display.setCurrent(menu);
    }
    
    public void showOptionMenu() {
        options.init();
        display.setCurrent(options);
    }
    
    public void saveConfig() {
        try {
            RmsFile.serialize(CONFIG_FILE, configuration);
        } catch (Exception e) {
        }        
    }
}
