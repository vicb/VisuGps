package fr.victorb.chart 
{    
    /**
    * ...
    * @author DefaultUser (Tools -> Custom Arguments...)
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