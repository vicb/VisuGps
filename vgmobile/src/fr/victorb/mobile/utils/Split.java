/*
 * Split.java
 *
 * Created on November 15, 2007, 10:13 PM
 *
 * To change this template, choose Tools | Template Manager
 * and open the template in the editor.
 */

package fr.victorb.mobile.utils;

/**
 *
 * @author Victor
 */
public class Split {
    private String delimiter = ",";
    private int pos;
    private String src;    

    /** Creates a new instance of Split */
    public Split(String src) {
        this.src = src;
    }

    public Split(String src, String delimiter) {
        this.src = src;
        this.delimiter = delimiter;
    }    

    public String next() {
        String sub;
        if (pos >= src.length()) {
            return null;
        } else {
            int match = src.indexOf(delimiter, pos);
            if (match == -1) {
                sub = src.substring(pos, src.length());
                pos = src.length();
            } else {
                sub = src.substring(pos, match);
                pos = match + 1;
            }
            return sub;
        }
    }
}
