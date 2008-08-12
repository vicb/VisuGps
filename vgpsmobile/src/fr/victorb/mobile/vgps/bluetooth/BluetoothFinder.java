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


package fr.victorb.mobile.vgps.bluetooth;

import fr.victorb.mobile.vgps.controller.Controller;
import java.util.Timer;
import java.util.TimerTask;
import java.util.Vector;
import javax.bluetooth.DeviceClass;
import javax.bluetooth.DiscoveryAgent;
import javax.bluetooth.DiscoveryListener;
import javax.bluetooth.LocalDevice;
import javax.bluetooth.RemoteDevice;
import javax.bluetooth.ServiceRecord;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.List;

public class BluetoothFinder extends List  implements DiscoveryListener, CommandListener {
    // List of devices found
    private Vector allDevices = new Vector();    
    // List of the address for the devices found 
    private Vector allDevicesAddress = new Vector();
    // List of devices founds supporting the requested service
    private Vector matchedDevices = new Vector();
    private boolean searchingDevices;
    private BluetoothFinderListener listener;
    
    private LocalDevice localDevice;
    private DiscoveryAgent discoveryAgent;
    
    private int searchedDeviceIdx;

    private static final int STATE_STOP = 0;
    private static final int STATE_SEARCHING_DEVICE  = 1;
    private static final int STATE_CANCEL = 3;
    
    private volatile int state;

    private Timer timer = new Timer();
    private TimerTask taskTitle = new TaskTitle();
    
    private Command cmdCancel = new Command("Cancel", Command.CANCEL, 1);
    private Command cmdSelect = new Command("Select", Command.ITEM, 1);
       
    /** 
     * Creates a new instance of BluetoothFinder
     * @param listener callback used to report search status
     */
    public BluetoothFinder(BluetoothFinderListener listener) {
        super("", List.IMPLICIT);
        this.listener = listener;
        addCommand(cmdCancel);
        setSelectCommand(cmdSelect);
        setCommandListener(this);       
        state = STATE_STOP;
    }

    /**
     * Start searching for bluetooth devices
     */
    public void startSearch() {
        if (state != STATE_STOP) {
            listener.deviceSearchCompleted(BluetoothFinderListener.SEARCH_ONGOING, null, null);
            return;
        } else {
            state = STATE_SEARCHING_DEVICE;
            timer.scheduleAtFixedRate(taskTitle, 0, 200);
            // Delete devices from previous search
            deleteAll();
            allDevices.removeAllElements();
            allDevicesAddress.removeAllElements();
            matchedDevices.removeAllElements();
            try {
                localDevice = LocalDevice.getLocalDevice();
                discoveryAgent = localDevice.getDiscoveryAgent();
                discoveryAgent.startInquiry(DiscoveryAgent.GIAC, this);
                searchingDevices = true;
            } catch (Exception e) {
                listener.deviceSearchCompleted(BluetoothFinderListener.BLUETOOTH_ERROR , null, null);
            }
        }        
    }    
   
    /**
     * Stop searching for bluetooth devices
     */
    public void cancelSearch() {
        switch (state) {
            case (STATE_SEARCHING_DEVICE) :
                discoveryAgent.cancelInquiry(this);
                state = STATE_CANCEL;
                break;
            default:
                break;
        }
    }
    
    /**
     * Add discovered devices to the device list
     * @param remoteDevice discovered device
     * @param deviceClass
     */
    public void deviceDiscovered(RemoteDevice remoteDevice, DeviceClass deviceClass) {
        String address = remoteDevice.getBluetoothAddress();
        if (allDevicesAddress.indexOf(address) == -1) {
            allDevices.addElement(remoteDevice);   
            allDevicesAddress.addElement(address);
            String name;
            try {
                name = remoteDevice.getFriendlyName(false);
            } catch (Exception e) {
                name = address;
            }
            Controller.getController().logAppend("Found Device: " + name);
            matchedDevices.addElement(new MatchedDevice(name, "btspp://" + address + ":1"));
            append(name, null);
        }
    }

    /**
     * Start searching services after devices have been found
     * @param i
     */
    public void inquiryCompleted(int i) {
        state = STATE_STOP;
    }    
    
    /**
     * Handle user inputs
     * @param command
     * @param displayable
     */
    public void commandAction(Command command, Displayable displayable) {
            cancelSearch();
            if (command == cmdSelect) {
                int index = getSelectedIndex();
                MatchedDevice deviceInfo = (MatchedDevice)matchedDevices.elementAt(index);
                listener.deviceSearchCompleted(BluetoothFinderListener.DEVICE_FOUND, 
                        deviceInfo.name, 
                        deviceInfo.url);
            } else if (command == cmdCancel) {
                listener.deviceSearchCompleted(BluetoothFinderListener.SEARCH_CANCELED, 
                        null,
                        null);            
            }
    }

    private class MatchedDevice  {
        public String name;
        public String url;

        public MatchedDevice(String name, String url) {
            this.name = name;
            this.url = url;
        }
    }

    /**
     * Update list title according to the search state
     */
    public class TaskTitle extends TimerTask {
        private String title = "Searching...";
        private int progress;
        public void run() {
            progress++;
            if (progress > title.length()) {
                progress = 1;
            }
            switch (state) {
                case STATE_SEARCHING_DEVICE:
                    setTitle(title.substring(0, progress));                    
                    break;
                case STATE_STOP:
                    setTitle("Device list");
                    timer.cancel();
                    break;
            }
        }                
    }

    public void servicesDiscovered(int arg0, ServiceRecord[] arg1) {
    }

    public void serviceSearchCompleted(int arg0, int arg1) {
    }
}
