package fr.victorb.chart 
{
    import com.hexagonstar.util.debug.Debug;
    /**
    * ...
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