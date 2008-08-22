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

package fr.victorb.mobile.vgps.gps;

import fr.victorb.mobile.utils.Split;
import fr.victorb.mobile.vgps.controller.Controller;
import java.io.IOException;
import java.io.InputStream;
import javax.microedition.io.Connector;

public class BluetoothGps extends Gps implements Runnable {

    private InputStream gpsStream;
    private boolean connected;
    private String url = new String();
    private GpsPosition position = new GpsPosition();
    private int elevation = 0;   
    
    /** Creates a new instance of BluetoothGps */
    public BluetoothGps() {
        super();
    }

    /**
     * Connect and start listening to a bluetooth GPS
     * @param url URL of the GPS
     * @return false
     */
    public boolean start(String url) {
        this.url = url;
        connected = false;
        try {
            new Thread(this).start();
        } catch (Exception ex) {            
        }
        return false;
    }

    /**
     * Stop listening to the bluetooth GPS
     */
    public void stop() {
        connected = false;       
    }
    
    /**
     * @return Last GPS position received
     */
    public GpsPosition getPosition() {  
        synchronized  (position) {
            return position;
        }
    }
    
    /**
     * Receive and decode GPS positions
     */
    public void run() {
        char c;
        Split split;
        String string;
        Controller controller = Controller.getController();
        
        try {            
            controller.logAppend("Connecting: " + url);
            gpsStream = Connector.openInputStream(url);    
            connected = true;
            controller.logAppend("Connected");
        } catch (IOException ex) { 
            controller.logAppend("Connection error: " + ex.getMessage());
            connected = false;
        }
               
        while (connected) {
            StringBuffer buffer = new StringBuffer();
            try {
                while((c = (char)gpsStream.read()) != '$'){}
                while((c = (char)gpsStream.read()) != 10) {
                    buffer.append(c);
                }                
                String nmea = buffer.toString();
                if (nmea.startsWith("GPGGA")) {
                    // Use GPGGA messages to get the validity of the fix
                    // and the elevation
                    boolean valid = false;
                    split = new Split(nmea);
                    split.next();       // GPGGA
                    split.next();       // time
                    split.next();       // lat
                    split.next();       // N/S
                    split.next();       // lng
                    split.next();       // E/W
                    string = split.next();                    
                    valid = (Integer.parseInt(string)) > 0;  // fix valid                    
                    split.next();       // nb satellites
                    split.next();       // h dilution
                    elevation =(int) Float.parseFloat(split.next()); // elevation                           
                    updatefixValid(valid);
                } else if (nmea.startsWith("GPRMC")) {
                    split = new Split(nmea);
                    split.next();           //GPRMC
                    string = split.next();  //time
                    split.next();           // status
                    synchronized  (position) {
                        position.time.hour = (byte)Integer.parseInt(string.substring(0, 2));
                        position.time.minute = (byte)Integer.parseInt(string.substring(2, 4));
                        position.time.second = (byte)Integer.parseInt(string.substring(4, 6));                    
                        position.latitude = Float.parseFloat(split.next()) * (split.next().toUpperCase().equals("N")?1:-1) / 100;
                        position.longitude = Float.parseFloat(split.next()) * (split.next().toUpperCase().equals("E")?1:-1) / 100;
                        position.elevation = elevation;                    
                        position.speed = Float.parseFloat(split.next()) * 1.852f;  // speed (knots -> km/h)    
                        split.next();                                              // heading 
                        string = split.next();
                        position.date.day = (byte)Integer.parseInt(string.substring(0, 2));
                        position.date.month = (byte)Integer.parseInt(string.substring(2, 4));
                        position.date.year = (byte)Integer.parseInt(string.substring(4, 6));                    
                    }
                    updatePosition(position);
                }                
            } catch(IOException ex) {
                // Conection error
                controller.logAppend("Connection error, trying to reconnect in 1min");
                try {
                    Thread.sleep(60 * 1000);
                    gpsStream = Connector.openInputStream(url);
                } catch (Exception e) {                    
                }                
            } catch (Exception ex) {
                // Parsing exception
            }            
        }
        try {
            gpsStream.close();                
        } catch (Exception e) {                
        }           
    }

    public boolean UseUtcTime() {
        return true;
    }
}
