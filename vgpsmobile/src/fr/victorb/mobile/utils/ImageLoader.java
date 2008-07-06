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

import java.io.ByteArrayOutputStream;
import java.io.DataInputStream;
import java.io.IOException;
import javax.microedition.io.Connector;
import javax.microedition.io.HttpConnection;
import javax.microedition.lcdui.Image;

public class ImageLoader {

    static public Image get(String url) {
        HttpConnection connection = null;
        DataInputStream stream = null;
        Image image = null;
        ByteArrayOutputStream imageStream = new ByteArrayOutputStream();
        int c;

        try {
            connection = (HttpConnection)Connector.open(url, Connector.READ);
            connection.setRequestMethod(HttpConnection.GET);
            stream = connection.openDataInputStream();
            while ((c = stream.read()) > -1) {
                imageStream.write(c);
            }
            image = Image.createImage(imageStream.toByteArray(), 0, imageStream.toByteArray().length);
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
            try {
                imageStream.close();
            } catch (Exception e) {
            }
        }                       
        return image;
    }   
}
