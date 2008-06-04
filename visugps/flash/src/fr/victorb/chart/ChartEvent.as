package fr.victorb.chart 
{
    import flash.events.MouseEvent;
    
    /**
    * Chart event
    * @author Victor Berchet
    */
    public class ChartEvent extends MouseEvent
    {
        
        public static const CLICK:String = "chart_click";
        public static const WHEEL_UP:String = "chart_wheel_up";
        public static const WHEEL_DOWN:String = "chart_wheel_down";
        public static const MOVE:String = "chart_move";
        
        private var _value:int;
        
        public function ChartEvent(type:String, value:int = 0) 
        {
            super(type);
            _value = value;
        }
        
        public function get value():int { return _value; }
        
    }
}