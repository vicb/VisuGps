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

public class Configuration implements Serializable {
    static private final int CFGVERSION = 2;
    private String gpsName = "No GPS";
    private String gpsUrl = "btspp://000A3A25DF6B:1";   
    private String pilotId = "";
    private short logInterval = 5;
    private short sendInterval = 5;
    private boolean useInternalGps = false;
    private boolean useAutoStart = true;
    private boolean useAutoStop = true;
    
    /** Creates a new instance of Configuration */
    public Configuration() {
    }

    public void setGpsName(String gpsName) {
        this.gpsName = gpsName;
    }

    public void setGpsUrl(String gpsUrl) {
        this.gpsUrl = gpsUrl;
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
   
    public String getPilotId() {
        return pilotId;
    }
    
    public short getLogInterval() {
        return logInterval;
    }
    
    public short getSendInterval() {
        return sendInterval;
    }
    
    public boolean getUseInternalGps() {
        return useInternalGps;
    }
    
    public void setUseInternalGps(boolean value) {
        useInternalGps = value;        
    }
    
    public boolean getUseAutoStart() {
        return useAutoStart;
    }
    
    public void setUseAutoStart(boolean value) {
        useAutoStart = value;
    }

    public boolean getUseAutoStop() {
        return useAutoStop;
    }
    
    public void setUseAutoStop(boolean value) {
        useAutoStop = value;
    }    
    
    public void serialize(DataOutputStream data) throws IOException {
        data.writeInt(Configuration.CFGVERSION);
        data.writeUTF(gpsName);
        data.writeUTF(gpsUrl);
        data.writeUTF(pilotId);
        data.writeShort(logInterval);
        data.writeShort(sendInterval);
        data.writeBoolean(useInternalGps);
        data.writeBoolean(useAutoStart);
        data.writeBoolean(useAutoStop);
    }
       
    public void unserialize(DataInputStream data) throws IOException, RmsSerializeException {
            if (data.readInt() != Configuration.CFGVERSION) {
                throw new RmsSerializeException("Bad configuration version");
            }
            gpsName = data.readUTF();
            gpsUrl = data.readUTF();
            pilotId = data.readUTF();
            logInterval = data.readShort();
            sendInterval = data.readShort();
            useInternalGps = data.readBoolean();
            useAutoStart = data.readBoolean();
            useAutoStop = data.readBoolean();
    }

}
