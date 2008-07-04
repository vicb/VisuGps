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

import fr.victorb.mobile.vgps.controller.Controller;
import javax.microedition.lcdui.Canvas;
import javax.microedition.lcdui.Graphics;
import javax.microedition.lcdui.Image;

public class ImageViewer extends Canvas {
    protected String url;
    private Image image = null;
    private Controller controller;
    
    public ImageViewer(String url) {
        super();
        setFullScreenMode(true);
        this.url = url;        
        controller = Controller.getController();
    }
    
    public ImageViewer start() {
        new Thread(new Helper()).start();
        return this;
    }

    protected void paint(Graphics g) {
        g.setColor(0xFFFFFF);
        g.fillRect(0, 0, getWidth(), getHeight());
        if (image != null) {
            g.drawImage(image, 0, 0, Graphics.TOP | Graphics.LEFT);
        } else {
            g.setColor(0x0000FF);
            g.drawString("Loading...", 0, g.getFont().getHeight() * 1, Graphics.BASELINE | Graphics.LEFT);        
        }
    }        
    
    protected void keyPressed(int keyCode) {
        controller.restoreDisplay();        
    }
        
    class Helper implements Runnable {
            public void run() {
                image = ImageLoader.get(url);
                repaint();
            }
    }

}
