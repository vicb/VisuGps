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

package fr.victorb.mobile.vgps.controller;

import fr.victorb.mobile.utils.GpsUtil;
import fr.victorb.mobile.vgps.ui.MainMenu;
import fr.victorb.mobile.vgps.bluetooth.BluetoothFinder;
import fr.victorb.mobile.vgps.bluetooth.BluetoothFinderListener;
import fr.victorb.mobile.vgps.config.Configuration;
import fr.victorb.mobile.vgps.gps.BluetoothGps;
import fr.victorb.mobile.vgps.gps.Gps;
import fr.victorb.mobile.vgps.gps.InternalGps;
import fr.victorb.mobile.vgps.rmsfile.RmsFile;
import fr.victorb.mobile.vgps.ui.OptionMenu;
import javax.bluetooth.UUID;
import javax.microedition.lcdui.Display;
import javax.microedition.midlet.MIDlet;

public class Controller implements BluetoothFinderListener {    
    private static Controller controller;
    private Display display;
    private boolean paused = false;
    private MIDlet midlet;
    public Configuration configuration = new Configuration();
    private static final String CONFIG_FILE = "config.ini";    
    private static final String VERSION = "v1.0";
    
    private int recordState = RecordState.STOP;
    
    private Gps gps;
    private GpsRecorder recorder;
    private GpsSender sender;
    
    private MainMenu menu;
    private OptionMenu options;
    
    /** Creates a new instance of Controller */
    private Controller() {
    }
    
    /**
     * Singleton 
     * @return Controller instance
     */
    static synchronized public Controller getController() {
        if (controller == null) {
            controller = new Controller();
            controller.init();
        }
        return controller;
    }

    /**
     * Initialize the controller
     */
    private void init() {
        menu = new MainMenu();
        options = new OptionMenu();
        if (GpsUtil.hasInternalGps()) {
            gps = new InternalGps();
        } else {
            gps = new BluetoothGps();    
        }                
        recorder = new GpsRecorder(gps);
        sender = new GpsSender(recorder);        
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
        if (GpsUtil.hasInternalGps()) {
            menu.setGpsName("Internal Gps");
        } else {
            menu.setGpsName(configuration.getGpsName());
        }
        display.setCurrent(menu);
    }
    
    public void pause() {
        paused = true;
    }

    public void exit() {
        midlet.notifyDestroyed();
    }

    /**
     * @return GPS currently in use
     */
    public Gps getGps() {
        return gps;
    }
    
    /**
     * @return Current record state
     */
    public int getRecordState() {
        return recordState;
    }
    
    /**
     * Start recording and sending position
     */
    public void startRecording() {
        gps.start(configuration.getGpsUrl());                       
        recorder.start();
        sender.start(); 
        recordState = RecordState.START;
    }
    
    /**
     * Stop recording and sending position
     */
    public void stopRecording() {
        sender.stop();
        recorder.stop();                        
        gps.stop();                        
        recordState = RecordState.STOP;        
    }
    
    /** 
     * Start searching bluetooth devices
     */
    public void searchDevice() {
        BluetoothFinder btFinder = new BluetoothFinder(this);;
        display.setCurrent(btFinder);
        btFinder.startSearch(new UUID[] {new UUID(0x1101)});        
    }
    
    /**
     * Set the bluetooth GPS to use
     * @param status Search status
     * @param deviceName Device name (when a device has been selected)
     * @param deviceUrl Device URL (when a device has been selected)
     */
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
    
    public Display getDisplay() {
        return display;
    }
    
    public String getVersion() {
        return VERSION;        
    }
}
