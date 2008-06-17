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
