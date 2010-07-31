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
    * Style of chart labels
    * @author Victor Berchet
    */
    public class LabelStyle 
    {
        private var _color:uint = 0x000000;
        private var _size:int = new int(9);
        
        public function LabelStyle() 
        {
            super();
        }
        
        public function get color():uint {return _color;}        
        public function set color(value:uint):void {_color = value;}
        
        public function get size():int {return _size;}        
        public function set size(value:int):void {_size = value;}
            
    }
    
}