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

package fr.victorb.mobile.utils;

import java.util.NoSuchElementException;

public class Split {
    private String delimiter = ",";
    private int pos;
    private String src;    

    /**
     * Constructor
     * @param src String to be splitted
     */
    public Split(String src) {
        this.src = src;
    }

    /**
     * Constructor
     * @param src String to be splitted
     * @param delimiter Delimiter
     */
    public Split(String src, String delimiter) {
        this.src = src;
        this.delimiter = delimiter;
    }    

    /**
     * Get the next part of the string
     * @return part of string or null when e/o string reached
     */
    public String next() throws NoSuchElementException {
        String sub;
        if (pos >= src.length()) {
            throw new NoSuchElementException("Reach the end of string");
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
