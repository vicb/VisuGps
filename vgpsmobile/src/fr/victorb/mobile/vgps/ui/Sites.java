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
import fr.victorb.mobile.vgps.controller.Controller;
import fr.victorb.mobile.vgps.controller.RecordState;
import fr.victorb.mobile.vgps.gps.Gps;
import fr.victorb.mobile.vgps.gps.GpsListener;
import fr.victorb.mobile.vgps.gps.GpsPosition;
import java.io.DataInputStream;
import java.io.IOException;
import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;
import javax.microedition.lcdui.Command;
import javax.microedition.lcdui.CommandListener;
import javax.microedition.lcdui.Displayable;
import javax.microedition.lcdui.Form;
import javax.microedition.lcdui.Item;
import javax.microedition.lcdui.ItemCommandListener;
import javax.microedition.lcdui.StringItem;

public class Sites extends Form implements CommandListener, ItemCommandListener {
    private StringItem weatherTxt;
    private Command cmdExit = new Command("Exit", Command.EXIT, 1);
    private Command cmdSelect = new Command("Select", Command.ITEM, 1);
    private Controller controller;
    private Thread thread;
    private Gps gps;  
    private Sites form;
    private GpsPosition position;
    
    public Sites() {
        super("Flying sites");
        controller = Controller.getController();
        addCommand(cmdExit);
        setCommandListener(this);      
        form = this;
    }
    
    public void start() {                
        thread = new Thread(new Helper());
        thread.start();
    }
    
    
    private class Helper implements GpsListener, Runnable {
        private boolean fixValid = false;
        
        public void run() {
            deleteAll();
            append(new StringItem("", "Waiting for a valid GPS fix"));
            gps = controller.getGps();
            if (controller.getRecordState() == RecordState.STOP) {
                // Start the GPS to get the location
                gps.start(controller.configuration.getGpsUrl());
            }
            gps.addFixValidListener(this);
            gps.addPositionListener(this);                                 
        }       
                
        public void gpsPositionUpdated(GpsPosition pos) {
            if (fixValid) {
                // Fetch weather info only once
                gps.removePositionListener(this);
                gps.removeFixValidListner(this);
                position = pos;
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
                StringBuffer distanceBuf;
                StringBuffer infoBuf; 
                int c;
                deleteAll();
                append(new StringItem("", "Retrieving sites info..."));
                String url = "http://www.victorb.fr/visugps/php/mvg_sites.php?" + 
                             "lat=" +  Converter.degMinToDeg(position.latitude) + 
                             "&lon=" + Converter.degMinToDeg(position.longitude);
                System.out.println(url);
                try {
                    connection = (HttpConnection)Connector.open(url, Connector.READ);
                    connection.setRequestMethod(HttpConnection.GET);
                    stream = connection.openDataInputStream();
                    deleteAll();
                    while (true) {
                        distanceBuf = new StringBuffer();
                        infoBuf = new StringBuffer();                         
                        while ((c = stream.read()) > 0xa) {
                            distanceBuf.append((char)c);
                        }
                        if (c == -1) break;
                        while ((c = stream.read()) > 0xa) {
                            infoBuf.append((char)c);
                        }
                        StringItem site = new StringItem(distanceBuf.toString(), infoBuf.toString(), StringItem.BUTTON);
                        site.setDefaultCommand(cmdSelect);
                        site.setItemCommandListener(form);
                        append(site);
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
        if (command == cmdExit) {
            if (controller.getRecordState() == RecordState.STOP) {
                // Stop the GPS if we started it
                gps.stop();
            }
            controller.showMainMenu();
        }
    }

    public void commandAction(Command command, Item item) {
        StringItem site = (StringItem)item;
        String coordinates = site.getText();
        int start = coordinates.indexOf("[") + 1;
        int middle = coordinates.indexOf(" ", start);
        float latSite = Float.parseFloat(coordinates.substring(start, middle));
        float lngSite = Float.parseFloat(coordinates.substring(middle + 1, coordinates.length() - 1));
        
        controller.viewMap(latSite, lngSite, 
                           Converter.degMinToDeg(position.latitude), Converter.degMinToDeg(position.longitude));       
        
    }

}
