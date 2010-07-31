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

package fr.victorb.chart 
{
    
    /**
    * Represents the type of a chart
    * @author Victor Berchet
    */
    public class ChartType 
    {
        static public const CHART_NONE:int = 0;
        static public const CHART_LINE:int = 1;
        static public const CHART_AREA:int = 2;
        
        private var _type:int = CHART_NONE;
        
        public function ChartType(type:int = CHART_NONE) {
            _type = type;
        }
        
        public function get type():int { return _type; }
        public function set type(value:int):void { _type = value; }
    
    }
    
}