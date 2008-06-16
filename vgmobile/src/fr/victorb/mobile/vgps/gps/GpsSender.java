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

import fr.victorb.mobile.vgps.controller.Controller;
import java.io.DataOutputStream;
import java.io.IOException;
import java.util.Timer;
import java.util.TimerTask;
import java.util.Vector;
import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;

/**
 *
 * @author a0919217
 */
public class GpsSender extends TimerTask {

    private Gps gps;
    private String id;
    private GpsRecorder recorder;
    
    private Vector positions = new Vector();
    private String url;
    
    private static final int STATE_START = 0;
    private static final int STATE_ON = 1;
    private static final int STATE_OFF = 2;
    private int state;
    
    public GpsSender(Gps gps, GpsRecorder recorder) {
        super();
        Controller controller = Controller.getController();
        this.gps = gps;        
        id = controller.configuration.getPilotId();
        url = controller.configuration.getLogUrl();
        this.recorder = recorder;
    } 
    
    public void run() {
        sendData();
    }
    
    public void start()  {
        state = STATE_START;
        positions.removeAllElements();
        Controller controller = Controller.getController();
        int period = controller.configuration.getSendInterval() * 60  * 1000;
        new Timer().scheduleAtFixedRate(this, period, period);  
        sendData();
    }
    
    public synchronized void stop() {
        cancel();
        state = STATE_OFF;
        sendData();
    }
    
    private synchronized void sendData() {     
        recorder.appendRecords(positions);
        
        String postData = new String();
        DataOutputStream stream = null;

        try {
            postData += "id=" + id;            
            if (state == STATE_START) {
                // date
                postData += "&start=1";
                state = STATE_ON;
            }
            if (state == STATE_OFF) {
                postData += "&stop=1";
            }
            for (int i = 0; i < positions.size(); i++) {                
                postData += "&gps[]="
                         + Float.toString(((GpsPosition)positions.elementAt(i)).latitude)
                         + ";"
                         + Float.toString(((GpsPosition)positions.elementAt(i)).longitude)
                         + ";"
                         + Integer.toString(((GpsPosition)positions.elementAt(i)).elevation)
                         + ";"
                         + Integer.toString(((GpsPosition)positions.elementAt(i)).time.hour)
                         + ";"
                         + Integer.toString(((GpsPosition)positions.elementAt(i)).time.minute)
                         + ";"
                         + Integer.toString(((GpsPosition)positions.elementAt(i)).time.second)
                         + ";"
                         + Integer.toString(((GpsPosition)positions.elementAt(i)).date.day)
                         + ";"
                         + Integer.toString(((GpsPosition)positions.elementAt(i)).date.month)
                         + ";"
                         + Integer.toString(((GpsPosition)positions.elementAt(i)).date.year);                         
            } 
        } catch (Exception e) {
        }
        HttpConnection connection = null;
        byte data[] = postData.getBytes();
        try {
            connection = (HttpConnection)Connector.open(url, Connector.WRITE);
            connection.setRequestMethod(HttpConnection.POST);
            connection.setRequestProperty("Content-Type","application/x-www-form-urlencoded");
            connection.setRequestProperty("Content-Length", data.length + "");
            stream = connection.openDataOutputStream();            
            stream.write(data, 0, data.length);
            stream.flush();
            stream.close();
            positions.removeAllElements();
        } catch (IOException e) {
            System.out.println("Sender error:" + e.getMessage());
        } finally {
            if (connection != null) {
                try {
                    connection.close();
                } catch (Exception e) {
                    System.out.println("Error:" + e.getMessage());
                }
            }
            try {
                stream.close();
            } catch (Exception e) {
                System.out.println("Error:" + e.getMessage());
            }
        }       
    }    
}
