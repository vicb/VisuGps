/*
 * Date.java
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
public class Date {
    public int year;
    public int month;
    public int day;   
    
    public Date clone() {
        Date date = new Date();
        date.year = year;
        date.month = month;
        date.day = day;
        return date;
    }
}
