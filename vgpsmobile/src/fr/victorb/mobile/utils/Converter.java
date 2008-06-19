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

public class Converter {

    /**
     * Convert a coordinate in degree.degree to degree.minute
     * @param degree coordinate in degree
     * @return coordinate in degree.minute
     */
    static public float degToDegMin(float degree){
        String value = String.valueOf(degree);
        int position = value.indexOf(".");
        if (position > 0) {
            String integer = value.substring(0, position);
            String decimal = value.substring(position + 1, value.length());
            decimal = "10." + decimal;
            decimal = String.valueOf(Float.parseFloat(decimal) * 6 / 10);
            return Float.parseFloat(integer) + Float.parseFloat(decimal) - 6;
        } else {
            return degree;
        }
    }
    
    
}
