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
    import com.hexagonstar.util.debug.Debug;
    /**
    * Represnts a serie to be drawn a the chart
    * @author Victor Berchet
    */
    public class Serie 
    {
        private var _color:uint = new uint();
        private var _name:String = new String();
        private var _data:Array = new Array();
        private var _min:Number = new Number();
        private var _max:Number = new Number();
        private var _type:ChartType = new ChartType();
        
        public function Serie(name:String, data:Array, type:ChartType, color:uint = 0xff0000) 
        {
            this._name = name;
            this._data = data;
            this._color = color;
            this._type = type;
            computeMinMax();
        }
        
        public function length():int { return data.length; }
        public function getValue(i:int):Number { return data[i]; }
        
        public function get color():uint { return _color; }
        public function set color(value:uint):void { _color = value; }
        
        public function get data():Array { return _data; }
        public function set data(value:Array):void { _data = value; computeMinMax(); }

        public function get type(): int { return _type.type; }
        public function set (value:int):void { _type = new ChartType(value); }
        
        public function get name():String { return _name; }
        
        public function get(i:int):Number { return _data[i]; }
        
        public function get min():Number { return _min; }
        
        public function get max():Number { return _max; }
        
        private function computeMinMax() : void {
            var i:int;
            _min = Number.MAX_VALUE;
            _max = Number.MIN_VALUE;
            for (i = 0; i < length(); i++) {
                if (data[i] < _min) _min = data[i];
                if (data[i] > _max) _max = data[i];
                
            }
        }        
    }    
}