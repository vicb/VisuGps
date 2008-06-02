package fr.victorb.chart 
{
	import com.hexagonstar.util.debug.Debug;
	import flash.events.Event;
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
			
		public function Charts() 
		{
			super();
			addEventListener("resize", doChartsLayout);
		}
		
		public function addChart(chart:Chart):void {
			Debug.trace("+addchart");
			charts.push(chart);
			addChild(chart);
			chart.x = 0;
			chart.y = 20;
			Debug.trace("+addchart 1");
			var slider:HSlider = new HSlider();
			slider.y = 10;
			slider.x = 0;
			slider.minimum = 0;
			slider.maximum = 100;
			slider.liveDragging = true;
			slider.sliderThumbClass =  MyThumb;
			slider.setStyle("fillColors", [ 0xFFFFFF, chart.getColor()]);
			slider.dataTipFormatFunction = formatTip(sliders.length);
			Debug.trace("+addchart 2");
			slider.addEventListener("change", onSliderChange);		
			sliders.push(slider);
			Debug.trace("+addchart 3");
			addChild(slider);
			Debug.trace("-addchart 4");
			
		}
			
		private function doChartsLayout(event:Event):void {
			Debug.trace("+chartsLayout");
			
			if (charts.length == 0) return
			
			Debug.trace("++chartsLayout");
			var sliderWidth:int = width / charts.length;
			
			for (var i:int = 0; i < charts.length; i++) {
				charts[i].width = width;
				charts[i].height = height - 20;
				sliders[i].x = i * sliderWidth;
				sliders[i].width = sliderWidth;
				charts[i].draw();
			}
			
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