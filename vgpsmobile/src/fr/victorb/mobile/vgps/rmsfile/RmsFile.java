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

package fr.victorb.mobile.vgps.rmsfile;

import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.DataInputStream;
import java.io.DataOutputStream;
import javax.microedition.rms.RecordStore;
import javax.microedition.rms.RecordStoreNotFoundException;

public class RmsFile {

    private static final int MAGIC = 0xBABA;
    private static final int MAXSIZE = 20;
    
    /** Creates a new instance of RmsFile */
    public RmsFile() {
    }
    
    /**
     * Serialize an object to the filesystem
     * @param filename Name of the file
     * @param data object
     * @throws fr.victorb.mobile.vgps.rmsfile.RmsSerializeException
     */
    static public void serialize(String filename, Serializable data) throws RmsSerializeException {
        ByteArrayOutputStream bos = new ByteArrayOutputStream();
        DataOutputStream dos = new DataOutputStream(bos);
        try {
            data.serialize(dos);
            save(filename, bos.toByteArray());
        } catch (Exception e) {  
            throw new RmsSerializeException(data.getClass().getName() + " serialization error : [" + e.getMessage() + "]");
        } finally {
            try {
                dos.close();
            } catch (Exception e) {
            }
        }
    }    
    
    /**
     * Save a file to the filesystem
     * @param filename name of the file
     * @param data content of the file
     * @throws fr.victorb.mobile.vgps.rmsfile.RmsFileException
     * @throws fr.victorb.mobile.vgps.rmsfile.RmsFileNotFoundException
     * @throws fr.victorb.mobile.vgps.rmsfile.RmsFileBadFormatException
     */
    static public void save(String filename, byte[] data) throws RmsFileException,
            RmsFileNotFoundException,
            RmsFileBadFormatException {
        DataInputStream dis = null;
        DataOutputStream dos = null;
        ByteArrayOutputStream bos = null;
        RecordStore rs = null;
        int firstRecord = 2;

        try {
            rs = RecordStore.openRecordStore(filename, true);
            int lastRecord = rs.getNextRecordID();
            if (lastRecord == 1) {
                rs.addRecord(new byte[] {0}, 0, 0);
            } else {
                dis = new DataInputStream(new ByteArrayInputStream(rs.getRecord(1)));
                if (dis.readInt() != MAGIC) {
                    throw new RmsFileBadFormatException(filename + " is not recognized");
                }
                firstRecord = dis.readInt();                                
                for (int idx = firstRecord; idx < lastRecord; idx++) {
                    rs.deleteRecord(idx);
                }
                firstRecord = lastRecord;                            
            }
                
            for (int length = data.length, offset = 0; length > 0; offset += MAXSIZE, length -= MAXSIZE) {
                rs.addRecord(data, offset, Math.min(length, MAXSIZE));                                        
            }

            bos = new ByteArrayOutputStream();
            dos = new DataOutputStream(bos);
            dos.writeInt(MAGIC);
            dos.writeInt(firstRecord);
            dos.writeInt(data.length);
            byte b[] = bos.toByteArray();
            rs.setRecord(1, b, 0, b.length);

        } catch (RecordStoreNotFoundException e) {
            throw new RmsFileNotFoundException(filename + " does not exist");
        } catch (RmsFileBadFormatException e) {
            throw new RmsFileBadFormatException(e.getMessage());
        } catch (Exception e) {
            throw new RmsFileException(e.getMessage());
        } finally {
            try {
                rs.closeRecordStore();                
            } catch (Exception e) {
            }
            try {
                dis.close();
            } catch (Exception e) {
            }
            try {
                dos.close();
            } catch (Exception e) {
            }                     
        }        
    }   
    
    /**
     * Unserialize an object from the filesystem
     * @param filename Name of the file
     * @param data Object
     * @throws fr.victorb.mobile.vgps.rmsfile.RmsSerializeException
     */
    static public void unserialize(String filename, Serializable data) throws RmsSerializeException {
        ByteArrayInputStream bis = null;
        DataInputStream dis = null;
        try {
            bis = new ByteArrayInputStream(load(filename));
            dis = new DataInputStream(bis);
            data.unserialize(dis);
        } catch (Exception e) {
            throw new RmsSerializeException(data.getClass().getName() + " unserialization error : [" + e.getMessage() + "]");
        } finally {
            try {
                dis.close();
            } catch (Exception e) {
            }
        }        
    }    
           
    /**
     * Load the content of a file from the filesystem
     * @param filename
     * @return
     * @throws fr.victorb.mobile.vgps.rmsfile.RmsFileException
     * @throws fr.victorb.mobile.vgps.rmsfile.RmsFileNotFoundException
     * @throws fr.victorb.mobile.vgps.rmsfile.RmsFileBadFormatException
     */
    static public byte[] load(String filename) throws RmsFileException, 
            RmsFileNotFoundException, 
            RmsFileBadFormatException {        
        RecordStore rs = null;
        byte[] data = null;
        ByteArrayOutputStream out = null;
        DataInputStream file = null;
        
        try {
            rs = RecordStore.openRecordStore(filename, false);
            file = new DataInputStream(new ByteArrayInputStream(rs.getRecord(1)));
            if (file.readInt() != MAGIC) {
                throw new RmsFileBadFormatException(filename + " is not recognized");
            }
            int firstRecord = file.readInt();
            int length = file.readInt();
            int lastRecord = rs.getNextRecordID();
            out = new ByteArrayOutputStream();            
            for (int idx = firstRecord; idx < lastRecord; idx++) {
                out.write(rs.getRecord(idx));                
            }
            data = out.toByteArray();            
        } catch (RecordStoreNotFoundException e) {
            throw new RmsFileNotFoundException(filename + " does not exist");
        } catch (RmsFileBadFormatException e) {
            throw new RmsFileBadFormatException(e.getMessage());
        } catch (Exception e) {
            throw new RmsFileException(e.getMessage());
        } finally {
            try {
                rs.closeRecordStore();                
            } catch (Exception e) {
            }
            try {
                file.close();
            } catch (Exception e) {
            }
            try {
                out.close();
            } catch (Exception e) {
            }                    
        }        
       return data; 
    }
    
    
    
}
