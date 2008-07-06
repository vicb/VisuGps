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

import fr.victorb.mobile.utils.Converter;
import fr.victorb.mobile.vgps.Constant;
import fr.victorb.mobile.vgps.controller.Controller;
import fr.victorb.mobile.vgps.controller.RecordState;
import fr.victorb.mobile.vgps.gps.Gps;
import fr.victorb.mobile.vgps.gps.GpsListener;
import fr.victorb.mobile.vgps.gps.GpsPosition;
import java.io.DataInputStream;
import java.io.EOFException;
import java.io.IOException;
import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Form;
import javax.microedition.lcdui.StringItem;

public class Weather extends Form implements CommandListener {
    private StringItem weatherTxt;
    private Command cmdExit = new Command("Exit", Command.EXIT, 1);
    private Controller controller;
    private Helper helper;
    private Gps gps;  
    
    public Weather() {
        super("Weather");
        controller = Controller.getController();
        append(weatherTxt = new StringItem("", ""));
        addCommand(cmdExit);
        setCommandListener(this);
    }
    
    public void start() {        
        new Thread(helper = new Helper()).start();
    }
    
    
    private class Helper implements GpsListener, Runnable {
        private boolean fixValid = false;
        private GpsPosition position;
        
        
        public void run() {
            weatherTxt.setText("Waiting for a valid GPS fix");
            gps = controller.getGps();
            if (controller.getRecordState() == RecordState.STOP) {
                // Start the GPS to get the location
                gps.start(controller.configuration.getGpsUrl());
            }
            gps.addFixValidListener(this);
            gps.addPositionListener(this);                                 
        }    
        
        public void gpsPositionUpdated(GpsPosition position) {
            if (fixValid) {
                // Fetch weather info only once
                gps.removePositionListener(this);
                gps.removeFixValidListner(this);
                this.position = position;
                new Thread(new HttpHelper()).start();                
            }
        }
        
        public void gpsFixValidUpdated(boolean valid) {
            fixValid = valid;
        }        
        
        private class HttpHelper implements Runnable {

            public void run() {
                HttpConnection connection = null;
                DataInputStream stream = null;
                StringBuffer buffer = new StringBuffer();
                weatherTxt.setText("Retrieving weather info...");
                String url = Constant.WEATHERURL + 
                             "?lat=" +  Converter.degMinToDeg(position.latitude) + 
                             "&lon=" + Converter.degMinToDeg(position.longitude);
                try {
                    connection = (HttpConnection)Connector.open(url, Connector.READ);
                    connection.setRequestMethod(HttpConnection.GET);
                    stream = connection.openDataInputStream();
                    try {
                        while(true)  {
                            buffer.append((char)stream.readByte());                            
                        }                        
                    } catch (EOFException e) {
                        weatherTxt.setText(buffer.toString());
                    }
                } catch (IOException e) {
                    weatherTxt.setText("Connection error!");
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
        }       
    }
    
    public void commandAction(Command command, Displayable display) {
        if (controller.getRecordState() == RecordState.STOP) {
            // Stop the GPS if we started it
            gps.stop();
        }
        gps.removePositionListener(helper);
        gps.removeFixValidListner(helper);
        controller.showMainMenu();
                        
    }

}
