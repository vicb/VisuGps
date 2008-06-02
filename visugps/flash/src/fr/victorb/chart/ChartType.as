package fr.victorb.chart 
{
	
	/**
	* ...
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