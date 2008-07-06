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

// This code is inspired by BlueGps <http://www.digitalmobilemap.com/>

import javax.microedition.midlet.*;
import javax.microedition.lcdui.*;
import javax.bluetooth.*;
import javax.microedition.io.*;
import java.io.*;

public class BlueGps extends MIDlet implements Runnable
{
    private Display display; 
    private TestCanvas canvas;

    LocalDevice localDevice;
    ServiceRecord serviceRecord;

    StreamConnectionNotifier notifier = null;
    StreamConnection conn = null;
    private static final UUID L2CAP_UUID = new UUID(256L); // simulate L2CAP service provided by real bluetooth GPS receiver.
    private static String serverUrl = "btspp://localhost:" + L2CAP_UUID + ";authorize=false;encrypt=false"; // simulate no authorize and no encrypt required by real bluetooth GPS receiver.
    OutputStream output;

    private boolean exitApp = false;
    
    String[] sentence;

    // Time in ms to wait until send next NMEA Sentence.
    private static final long BREAK = 5000;

    public BlueGps()
    {
        display = Display.getDisplay(this);
        canvas = new TestCanvas(this);
        display.setCurrent(canvas);
    }

    protected void startApp()
    {
        new Thread(this).start();
    }

    public void run()
    {       
        InputStream igcInputStream;        
        igcInputStream = getClass().getResourceAsStream("/track.igc");
        DataInputStream igcStream = new DataInputStream(igcInputStream);
        
        canvas.message = "Starting BlueGPS...";
        canvas.paintScreen();

        try
        {
            localDevice = LocalDevice.getLocalDevice();
            localDevice.setDiscoverable(DiscoveryAgent.GIAC);
            notifier = (StreamConnectionNotifier)Connector.open(serverUrl);
        }
        catch (BluetoothStateException e)
        {
            canvas.message = e.getMessage();
            canvas.paintScreen();
            return;
        }
        catch (IOException e)
        {
            canvas.message = e.getMessage();
            canvas.paintScreen();
            return;
        }

        try {
            canvas.message = "Waiting for connections...";
            canvas.sentence = "";
            canvas.paintScreen();
            conn = notifier.acceptAndOpen();
            output = conn.openOutputStream();
        } catch (IOException e) {
            canvas.message = e.getMessage();
            canvas.paintScreen();
        }

        char igcChar;
        String date = new String("010101");
            
        while (!exitApp)
        {
            StringBuffer igcBuffer = new StringBuffer(); 
            String igcSentence = new String();
            try {
                while (igcStream.readByte() != 0xa) {}
                while (true) {
                    igcChar = (char)igcStream.readByte();
                    if (igcChar == 0x0d) break;
                    igcBuffer.append(igcChar);
                }
            }  catch (Exception e) {
                break;
            }
            
            igcSentence = igcBuffer.toString();
           
            if (igcSentence.startsWith("HFDTE")) {
                date = igcSentence.substring(5, 11);
            }
            
            if (igcSentence.startsWith("B")) {           
                String gpggaSentence = new String();
                String gprmcSentence = new String();
                
                gpggaSentence = "$GPGGA," +
                                igcSentence.substring(1, 7) + "," +
                                igcSentence.substring(7, 11) + "." + igcSentence.substring(11, 14) + "," + igcSentence.substring(14, 15) + "," +
                                igcSentence.substring(15, 20) + "." + igcSentence.substring(20, 23) + "," + igcSentence.substring(23, 24) + "," +
                                "1,08,0.9," +
                                igcSentence.substring(25,30) + "," +
                                "M,46.9,M,,*47";
                
                gprmcSentence = "$GPRMC," +
                                igcSentence.substring(1, 7) + "," +
                                "A," +
                                igcSentence.substring(7, 11) + "." + igcSentence.substring(11, 14) + "," + igcSentence.substring(14, 15) + "," +
                                igcSentence.substring(15, 20) + "." + igcSentence.substring(20, 23) + "," + igcSentence.substring(23, 24) + "," +
                                "005.0,315.0," + date + ",000.0,E*6F";
                
                try  {
                    canvas.message = "Waiting to send...";
                    canvas.sentence = gpggaSentence;
                    canvas.paintScreen();

                    output.write(gpggaSentence.getBytes());
                    output.write(13);
                    output.write(10);
                    output.write(gprmcSentence.getBytes());
                    output.write(13);
                    output.write(10);
                    output.flush();
                }
                catch (IOException e) {
                    canvas.message = e.getMessage();
                    canvas.paintScreen();
                }

                try  {
                    if (!exitApp) Thread.sleep(BREAK); // wait for a while before send next sentence
                }
                catch (Exception e) {}
            }
        }
        //
        try {
            output.close();
            igcStream.close();
            conn.close();
            notifier.close();
        } catch (Exception e) {            
        }
        notifyDestroyed();
    }

    protected void pauseApp() { }

    protected void destroyApp( boolean unconditional ) { }

    class TestCanvas extends Canvas implements CommandListener
    {
        private BlueGps midlet;
        public String message;
        private int width, height;
        public String sentence;

        public TestCanvas(BlueGps pmidlet)
        {
            midlet = pmidlet;
            setFullScreenMode(true);
            width = getWidth();
            height = getHeight();
            message = new String();
            sentence = new String();
            setCommandListener(this);
        }

        public void paintScreen()
        {
            repaint();
            serviceRepaints();
        }

        protected void paint(Graphics g)
        {
            g.setColor(0xCCFFCC);
            g.fillRect(0, 0, width, height);

            g.setColor(0x0000FF);
            g.drawString(message, 0, g.getFont().getHeight() * 1, Graphics.BASELINE | Graphics.LEFT);
            g.drawString(sentence, 0, g.getFont().getHeight() * 2, Graphics.BASELINE | Graphics.LEFT);
            g.drawString("Press any key to exit", 0, g.getFont().getHeight()*3, Graphics.BASELINE | Graphics.LEFT);
        }

        protected void keyPressed(int key)
        {
            exitApp = true; 
            message = sentence = "Exiting...";
            paintScreen();
        }

        public void commandAction(Command c, Displayable d) {}
    }
}