/*
 * Serializable.java
 *
 * Created on November 6, 2007, 7:31 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.rmsfile;

import java.io.DataInputStream;
import java.io.DataOutputStream;
import java.io.IOException;

/**
 *
 * @author Victor
 */
public interface Serializable {

    public void serialize(DataOutputStream data) throws IOException;
    public void unserialize(DataInputStream data) throws IOException, RmsSerializeException;
    
}
