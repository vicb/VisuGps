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

package fr.victorb.mobile.utils;

import fr.victorb.mobile.vgps.Constant;
import java.io.DataOutputStream;
import java.io.IOException;
import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;

public class GpsUtil {
    public static boolean hasInternalGps() {
        if (System.getProperty("microedition.location.version") != null) {
            return true;
        } else {
            return false;
        }
    }   
    
    public static void requestNetworkPermission() {
        DataOutputStream stream = null;
        HttpConnection connection = null;
        byte data[] = new String("perm=1").getBytes();
        try {
            connection = (HttpConnection)Connector.open(Constant.LOGURL, Connector.WRITE);
            connection.setRequestMethod(HttpConnection.POST);
            connection.setRequestProperty("Content-Type","application/x-www-form-urlencoded");
            connection.setRequestProperty("Content-Length", Integer.toString(data.length));
            stream = connection.openDataOutputStream();            
            stream.write(data, 0, data.length);
            stream.close();
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
}
