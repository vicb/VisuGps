package fr.victorb.chart 
{
	import com.hexagonstar.util.debug.Debug;
	import flash.display.Sprite;
	import flash.events.Event;
	import flash.events.MouseEvent;
	import fr.victorb.component.MyThumb;
	import mx.controls.HSlider;
	import mx.controls.sliderClasses.Slider;
	import mx.core.UIComponent;
	
	/**
	* ...
	* @author DefaultUser (Tools -> Custom Arguments...)
	*/
	public class Charts extends UIComponent
	{
		private var charts:Array = new Array();
		private var sliders:Array = new Array();
		private var cursor:Sprite;
		private var cursorCallback:Function = null;
			
		public function Charts(cursorCallback:Function) 
		{
			super();
			this.cursorCallback = cursorCallback;
			addEventListener("resize", doChartsLayout);
			addEventListener("mouseMove", onMouseMove);
		}
		
		public function addChart(chart:Chart):void {
			charts.push(chart);
			addChild(chart);
			chart.x = 0;
			chart.y = 20;
			var slider:HSlider = new HSlider();
			slider.y = 10;
			slider.x = 0;
			slider.minimum = 0;
			slider.maximum = 100;
			slider.liveDragging = true;
			slider.sliderThumbClass =  MyThumb;
			slider.setStyle("fillColors", [ 0xFFFFFF, chart.getColor()]);
			slider.dataTipFormatFunction = formatTip(sliders.length);
			slider.addEventListener("change", onSliderChange);		
			sliders.push(slider);
			addChild(slider);
		}
			
		private function onMouseMove(event:MouseEvent):void {
			if (cursor &&
				event.stageX >= charts[0].xMin &&
				event.stageX <= charts[0].xMax) {
					cursor.x = event.stageX;
					cursor.y = 0;
					cursorCallback(cursor.x - charts[0].xMin) * 1000 / (charts[0].xMax - charts[0].xMin);
				}
			
			
		}
		private function doChartsLayout(event:Event):void {	
			if (charts.length == 0) return
			
			var sliderWidth:int = width / charts.length;
			
			for (var i:int = 0; i < charts.length; i++) {
				charts[i].width = width;
				charts[i].height = height - 20;
				sliders[i].x = i * sliderWidth;
				sliders[i].width = sliderWidth;
				charts[i].draw();
			}
			
			if (cursor) removeChild(cursor);
			
			cursor = new Sprite();
			cursor.graphics.lineStyle(2, 0xffcc00);			
			cursor.graphics.moveTo(0, charts[0].yMin + 20);
			cursor.graphics.lineTo(0, charts[0].yMax + 20);			
			addChild(cursor);
			
			Debug.trace("-chartsLayout");	
			
		}
		
		private function formatTip(i:int):Function {
			var chart:Chart = charts[i];
			return function format(value:int):String { 
				       return chart.getName() + " : " +  value + "%"; 
				   }
		}
		
		public function setChartsAlpha(values:Array):void {
			for (var i:int = 0; i < values.length; i++) {
				charts[i].setAlpha(values[i] / 100);
				sliders[i].setThumbValueAt(0, values[i]);
			}		
			doChartsLayout(null);
		}
		
		private function onSliderChange(event:Event):void {
			var slider:Object = event.target;
			
			for (var i:int = 0; i < sliders.length; i++) {
				if (sliders[i] == slider) {
					charts[i].setAlpha(slider.value / 100);				
					break;
				}
			}
			
		}
		
		
	}
	
}