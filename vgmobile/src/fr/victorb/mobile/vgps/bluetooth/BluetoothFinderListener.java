/*
 * BluetoothFinderListener.java
 *
 * Created on November 11, 2007, 6:15 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.bluetooth;

/**
 *
 * @author Victor
 */
public interface BluetoothFinderListener {
    public static final int DEVICE_FOUND = 0;
    public static final int NO_DEVICE_FOUND = 100;
    public static final int BLUETOOTH_OFF = 101;
    public static final int BLUETOOTH_ERROR = 102;
    public static final int SEARCH_CANCELED = 103;
    public static final int SEARCH_ONGOING = 104;
    
    /** 
     * Callback reporting search status
     * @param status status of the query
     * @param deviceName name of the device when found
     * @param deviceUrl  url of the device when found
     */
    public void deviceSearchCompleted(int status, String deviceName, String deviceUrl);
    
}
