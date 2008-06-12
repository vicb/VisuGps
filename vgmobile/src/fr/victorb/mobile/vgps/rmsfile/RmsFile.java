/*
 * RmsFile.java
 *
 * Created on November 6, 2007, 7:27 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.vgps.rmsfile;

import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.DataInputStream;
import java.io.DataOutputStream;
import javax.microedition.rms.RecordStore;
import javax.microedition.rms.RecordStoreNotFoundException;

/**
 *
 * @author Victor
 */
public class RmsFile {

    private static final int MAGIC = 0xBABA;
    private static final int MAXSIZE = 20;
    
    /** Creates a new instance of RmsFile */
    public RmsFile() {
    }
    
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
