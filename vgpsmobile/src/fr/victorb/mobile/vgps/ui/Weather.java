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
import fr.victorb.mobile.vgps.gps.GpsPosition;
import fr.victorb.mobile.vgps.gps.Localize;
import java.io.DataInputStream;
import java.io.EOFException;
import java.io.IOException;
import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;
import javax.microedition.lcdui.StringItem;

public class Weather extends Localize {
    private GpsPosition position;
    
    public Weather() {
        super("Weather");
    }
    
    public void localize(GpsPosition position) {
        this.position = position;
        new Thread(new HttpHelper()).start();
    }    
        
    private class HttpHelper implements Runnable {

        public void run() {
            HttpConnection connection = null;
            DataInputStream stream = null;
            StringBuffer buffer = new StringBuffer();
            deleteAll();
            append(new StringItem("", "Retrieving weather info..."));
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
                    deleteAll();
                    append(new StringItem("", buffer.toString()));                    
                }
            } catch (IOException e) {
                deleteAll();
                append(new StringItem("", "Connection error!"));
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
