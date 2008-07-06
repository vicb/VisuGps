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

import fr.victorb.mobile.vgps.gps.*;
import java.io.DataOutputStream;
import java.io.IOException;
import java.util.Timer;
import java.util.TimerTask;
import java.util.Vector;
import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;

public class GpsSender {

    private String id;
    private GpsRecorder recorder;
    
    private Vector positions = new Vector();
    private String url;
    
    private static final int STATE_START = 0;
    private static final int STATE_ON = 1;
    private static final int STATE_OFF = 2;
    private int state;
    
    Controller controller;
    
    private Timer timer;
    
    public GpsSender(GpsRecorder recorder) {
        super();
        this.recorder = recorder;
        controller = Controller.getController();
    } 
      
    /**
     * Start sending positions
     */
    public void start()  {
        state = STATE_START;
        positions.removeAllElements();
        id = controller.configuration.getPilotId();
        url = controller.configuration.getLogUrl();
        int period = controller.configuration.getSendInterval() * 60  * 1000;
        timer = new Timer();
        timer.scheduleAtFixedRate(new Helper(), 100, period);  
    }
    
    /**
     * Stop sending positions
     */
    public synchronized void stop() {
        timer.cancel();
        state = STATE_OFF;
        new Thread(new Runnable() {public void run(){sendData();}}).start();
    }
    
    /**
     * Send positions
     */
    private synchronized void sendData() {     
        recorder.appendRecords(positions);
        
        String postData = new String();
        DataOutputStream stream = null;

        try {
            postData += "id=" + id;            
            if (state == STATE_START) {
                postData += "&start=1";
                state = STATE_ON;
            }
            if (controller.getGps().UseUtcTime()) {
                postData += "&utc=1";
            }
            if (state == STATE_OFF) {
                postData += "&stop=1";
            }
            for (int i = 0; i < positions.size(); i++) {   
                GpsPosition fix = (GpsPosition)positions.elementAt(i);
                postData += "&gps[]="
                         + Float.toString(fix.latitude)  + ";"
                         + Float.toString(fix.longitude) + ";"
                         + Integer.toString(fix.elevation) + ";"
                         + Integer.toString(fix.time.hour) + ";"
                         + Integer.toString(fix.time.minute) + ";"
                         + Integer.toString(fix.time.second) + ";"
                         + Integer.toString(fix.date.day) + ";"
                         + Integer.toString(fix.date.month) + ";"
                         + Integer.toString(fix.date.year);                        
            } 
        } catch (Exception e) {
        }
        HttpConnection connection = null;
        byte data[] = postData.getBytes();
        try {
            connection = (HttpConnection)Connector.open(url, Connector.WRITE);
            connection.setRequestMethod(HttpConnection.POST);
            connection.setRequestProperty("Content-Type","application/x-www-form-urlencoded");
            connection.setRequestProperty("Content-Length", Integer.toString(data.length));
            stream = connection.openDataOutputStream();            
            stream.write(data, 0, data.length);
            stream.close();
            positions.removeAllElements();
        } catch (IOException e) {            
        } finally {
            if (connection != null) {
                try {
                    connection.close();
                } catch (Exception e) {
                }
            }
            try {
                stream.close();
            } catch (Exception e) {
            }
        }       
    }    
    
    private class Helper extends TimerTask {
        public void run() {
            sendData();
        }
    }
}
