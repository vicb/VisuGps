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


package fr.victorb.mobile.vgps.config;

import fr.victorb.mobile.vgps.rmsfile.RmsSerializeException;
import fr.victorb.mobile.vgps.rmsfile.Serializable;
import java.io.DataInputStream;
import java.io.DataOutputStream;
import java.io.IOException;

/**
 *
 * @author Victor
 */
public class Configuration implements Serializable {
    static private final int CFGVERSION = 1;
    private String gpsName = "BlueGPS";
    private String gpsUrl = "btspp://000A3A25DF6B:1;authenticate=false;encrypt=false;master=true";
    private String logUrl = "http://www.victorb.fr/visugps/php/mvg_track.php";
    private String pilotId = "";
    private short logInterval = 10;
    private short sendInterval = 10;
    
    /** Creates a new instance of Configuration */
    public Configuration() {
    }

    public void setGpsName(String gpsName) {
        this.gpsName = gpsName;
    }

    public void setGpsUrl(String gpsUrl) {
        this.gpsUrl = gpsUrl;
    }

    public void setLogUrl(String logUrl) {
        this.logUrl = logUrl;
    }
    
    public void setPilotId(String pilotId) {
        this.pilotId = pilotId;
    }
    
    public void setLogInterval(int value) {
        this.logInterval = (short)value;
    }
    
    public void setSendInterval(int value) {
        this.sendInterval = (short)value;
    }
    
    public String getGpsName() {
        return gpsName;
    }

    public String getGpsUrl() {
        return gpsUrl;
    }

    public String getLogUrl() {
        return logUrl;
    }
    
    public String getPilotId() {
        return pilotId;
    }
    
    public short getLogInterval() {
        return logInterval;
    }
    
    public short getSendInterval() {
        return sendInterval;
    }
    
    public void serialize(DataOutputStream data) throws IOException {
        data.writeInt(Configuration.CFGVERSION);
        data.writeUTF(gpsName);
        data.writeUTF(gpsUrl);
        data.writeUTF(logUrl);
        data.writeUTF(pilotId);
        data.writeShort(logInterval);
        data.writeShort(sendInterval);
    }
       
    public void unserialize(DataInputStream data) throws IOException, RmsSerializeException {
            if (data.readInt() != Configuration.CFGVERSION) {
                throw new RmsSerializeException("Bad configuration version");
            }
            gpsName = data.readUTF();
            gpsUrl = data.readUTF();
            logUrl = data.readUTF();
            pilotId = data.readUTF();
            logInterval = data.readShort();
            sendInterval = data.readShort();
    }

}
