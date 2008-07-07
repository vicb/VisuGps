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

import fr.victorb.mobile.utils.*;
import fr.victorb.mobile.vgps.controller.Controller;
import javax.microedition.lcdui.Canvas;
import javax.microedition.lcdui.Graphics;
import javax.microedition.lcdui.Image;
import javax.microedition.lcdui.game.Sprite;

public class ImageViewer extends Canvas {
    protected String url, error;
    private Image image = null;
    private Controller controller;
    private int x, y, progress, total;
    
    public ImageViewer(String url) {
        super();
        setFullScreenMode(true);
        this.url = url;        
        controller = Controller.getController();
    }
    
    public ImageViewer start() {
        progress = total = 0;
        new Thread(new Helper()).start();
        return this;
    }

    protected void paint(Graphics g) {
        g.setColor(0xFFFFFF);
        g.fillRect(0, 0, getWidth(), getHeight());
        if (error != null) {
            // Display error message
            int fontHeight = g.getFont().getHeight();
            g.setColor(0x0000FF);
            g.drawString(error, 0, fontHeight, Graphics.BASELINE | Graphics.LEFT);             
        } else if (image != null) {
            // Display the image
            g.drawRegion(image, x, y, getWidth(), getHeight(), Sprite.TRANS_NONE, 0, 0, Graphics.TOP | Graphics.LEFT);
        } else {
            // Display loading message together with a progress bar
            int fontHeight = g.getFont().getHeight();
            g.setColor(0x0000FF);
            g.drawString("Loading...", 0, fontHeight, Graphics.BASELINE | Graphics.LEFT);             
            g.setColor(0x000000);
            g.drawRect(0, fontHeight + 5, getWidth() - 1, 10);            
            if (total > 0) {
                g.setColor(0x0000FF);
                for (int i = 1; i < (getWidth() - 2) * progress / total; i++) {
                    g.drawLine(i, fontHeight + 6, i, fontHeight + 14);                    
                }
            }
            
        }
    }        
    
    protected void keyPressed(int keyCode) {
        switch (getGameAction(keyCode)) {
            case Canvas.UP: 
                y = Math.max (0, y - 30);
                break;
            case Canvas.LEFT:
                x = Math.max(0, x - 30);
                break;
            case Canvas.DOWN:
                if (image != null) y = Math.min(image.getHeight() - getHeight(), y + 30);
                break;
            case Canvas.RIGHT:
                if (image != null) x= Math.min(image.getWidth() - getWidth(), x + 30);
                break;
            case Canvas.FIRE:
                controller.restoreDisplay();
        }                
        repaint();
    }
    
    protected void keyRepeated(int keyCode) {
        keyPressed(keyCode);
    }
        
    class Helper implements Runnable, ProgressListener {
            public void run() {
                image = ImageLoader.get(url, this);
                if (image != null) {
                    x = Math.max((image.getWidth() - getWidth()) / 2, 0);
                    y = Math.max((image.getHeight() - getHeight()) / 2, 0);
                    error = null;
                } else {
                    error = new String("Connection error!");
                }
                repaint();
            }

        public void downloadProgress(int value, int max) {
            progress = value;
            total = max;
            repaint();
        }
    }

}
