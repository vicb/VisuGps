/*
 * VGpsMobile.java
 *
 * Created on October 30, 2007, 8:50 PM
 */

package fr.victorb.mobile.vgps;

import fr.victorb.mobile.vgps.controller.Controller;
import javax.microedition.midlet.*;

/**
 *
 * @author  Victor
 * @version
 */
public class VGpsMobile extends MIDlet {
    private Controller controller;
    
    public void startApp() {
        controller = Controller.getController();
        controller.start(this);
    }
    
    public void pauseApp() {
        controller.pause();
    }
    
    public void destroyApp(boolean unconditional) {
    }
}
