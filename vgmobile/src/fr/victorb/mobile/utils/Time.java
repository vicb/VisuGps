/*
 * Time.java
 *
 * Created on November 15, 2007, 10:12 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.utils;

/**
 *
 * @author Victor
 */
public class Time {
    public int hour;
    public int minute;
    public int second;
    
    public Time clone() {
        Time time = new Time();
        time.hour = hour;
        time.minute = minute;
        time.second = second;
        return time;
    }
}
