package fr.victorb.chart 
{
	import flash.display.Shape;
	import flash.text.TextField;
	import flash.text.TextFieldAutoSize;
	import flash.text.TextFormat;
	import flash.text.TextFormatAlign;
	import mx.core.UIComponent;
	import com.hexagonstar.util.debug.Debug;
	import fr.victorb.chart.ChartType;
	import flash.display.Sprite;
	
	/**
	* ...
	* @author DefaultUser (Tools -> Custom Arguments...)
	*/
	public class Chart extends UIComponent
	{
		private var _xLabelStyle:LabelStyle = new LabelStyle();
		private var _yLabelStyle:LabelStyle = new LabelStyle();
		private var series:Array = new Array();
		private var min:Number = new Number();
		private var max:Number = new Number();	
		
		private var topPadding:int = 15;
		private var bottomPadding:int = 2;
		private var leftPadding:int = 2;
		private var rightPadding:int = 2;

		private var xMin:int;
		private var xMax:int;
		private var yMin:int;
		private var yMax:int;
		private var xStep:Number;
		private var yStep:Number;
		
		private var xLabels:Array = new Array();
		private var yDensity:int = 5 ;
		
		
		public function Chart() 
		{
			super();			
		}
		
		public function setHorizontalLabels(value:Array):void { xLabels = value; }
		public function setVerticalDensity(value:int):void { yDensity = value; }	

		public function get xLabelStyle():LabelStyle { return _xLabelStyle; }
		public function set xLabelStyle(value:LabelStyle):void { _xLabelStyle = value; }

		public function get yLabelStyle():LabelStyle { return _yLabelStyle; }
		public function set yLabelStyle(value:LabelStyle):void { _yLabelStyle = value; }
		
		public function addSerie(serie:Serie):void {
			series.push(serie);
		}
		
		public function setAlpha(alpha:Number):void {
			this.alpha = alpha;
			for (var j:int = 0; j < numChildren; j++) {
				getChildAt(j).alpha = alpha;
			}					
		}
		
		public function draw() : void
		{
			graphics.clear();
			
			var j:int;
			for (j = 0; j < numChildren; j++) {
				if (getChildAt(j) is TextField) removeChildAt(j--);
			}
			
			if (series.length == 0) return;
			
			computeMinMax();
			
			var txt:TextField = new TextField();
			txt.text = "9999";
			txt.autoSize = TextFieldAutoSize.LEFT;
			txt.setTextFormat(new TextFormat("Verdana", yLabelStyle.size));
			
			xMin = leftPadding + 8 + txt.width;
			xMax = width - rightPadding;
			Debug.trace("xMax: " + xMax);
			
			txt.setTextFormat(new TextFormat("Verdana", xLabelStyle.size));
			
			yMin = topPadding;
			yMax = height - bottomPadding - txt.height - 6;
			
			yStep = (yMax - yMin) / (max - min);
			drawGrid();
			drawVerticalAxis();
			drawHorizontalAxis();
			
			var s:int;
			var i:int;
					
			for (s = 0; s < series.length; s++) {				
				xStep = (xMax - xMin) / series[s].length();				
				if (series[s].type == ChartType.CHART_LINE) {
					drawLine(series[s]);
				} else if (series[s].type == ChartType.CHART_AREA) {
					drawArea(series[s]);
				}
					
			}			
		}
		
		public function getName():String {
			return (series.length)?series[0].name:"";
		}
		
		public function getColor():uint {
			return (series.length)?series[0].color:0;
		}

		private function drawLine(serie:Serie):void {
			graphics.lineStyle(1, serie.color);
			graphics.moveTo(xMin, yMax - (serie.getValue(0) - min) * yStep);
			for (var i:int = 1; i < serie.length(); i++) {
				graphics.lineTo(xMin + i * xStep, yMax - (serie.getValue(i) - min) * yStep);
			}			
		}
		
		private function drawArea(serie:Serie):void {
			graphics.lineStyle(1, serie.color);
			graphics.moveTo(xMin, yMax - 1);
			graphics.beginFill(serie.color, 1);					
			graphics.lineTo(xMin, yMax - (serie.getValue(0) - min) * yStep);
			for (var i:int = 1; i < serie.length(); i++) {
				graphics.lineTo(xMin + i * xStep, yMax - (serie.getValue(i) - min) * yStep);
			}
			graphics.lineTo(xMax, yMax - 1);					
			graphics.endFill();						
		}		
		
		private function drawGrid():void {
			graphics.lineStyle(1, 0xbbbbbb);
			
			var y:int;
			
			for (var i:int = 0; i < yDensity; i++) {
				y = yMin + (max - min) * yStep * i / (yDensity - 1);
				graphics.moveTo(xMin, y);
				graphics.lineTo(xMax, y);
				
			}
			
			var x:int;
			
			for (i = 0; i < xLabels.length; i++) {
				x = xMin + (xMax - xMin) * i / (xLabels.length - 1);
				graphics.moveTo(x, yMin);
				graphics.lineTo(x, yMax);				
				
			}
			
		}
		
		
		private function drawVerticalAxis():void {
			graphics.lineStyle(1);
			
			graphics.moveTo(xMin, yMin);
			graphics.lineTo(xMin, yMax);
			
			var y:int;
			var yLabel:Number = max;
			
			for (var i:int = 0; i < yDensity; i++) {
				y = yMin + (max - min) * yStep * i / (yDensity - 1);
				
				graphics.moveTo(xMin, y);
				graphics.lineTo(xMin - 5, y);
				
				var txt:TextField = new TextField();
				txt.text = Math.round(yLabel).toString();
				
				txt.selectable = false;
				txt.cacheAsBitmap = true;
				txt.setTextFormat(new TextFormat("Verdana", yLabelStyle.size, yLabelStyle.color));
				txt.autoSize = TextFormatAlign.RIGHT;
				txt.x = xMin - 8 - txt.width;
				txt.y = y - txt.height / 2;
				
				addChild(txt);
			
				
				yLabel -= (max - min) / (yDensity - 1);
			}
			
		}

		private function drawHorizontalAxis():void {
			graphics.lineStyle(1);
			
			graphics.moveTo(xMin, yMax);
			graphics.lineTo(xMax, yMax);
			
			var x:int;
			
			for (var i:int = 0; i < xLabels.length; i++) {
				x = xMin + (xMax - xMin) * i / (xLabels.length - 1);
				graphics.moveTo(x, yMax);
				graphics.lineTo(x, yMax + 5);
				
				var txt:TextField = new TextField();
				txt.text = xLabels[i].toString();
				txt.selectable = false;
				txt.setTextFormat(new TextFormat("Verdana", xLabelStyle.size, xLabelStyle.color));
				txt.autoSize = TextFormatAlign.RIGHT;
				txt.x = x - txt.width / 2;
				txt.x = Math.max(xMin, txt.x);
				txt.x = Math.min(xMax - txt.width, txt.x);
				txt.y = yMax + 6;
				
				addChild(txt);
				
				
			}
			
		}		
				
		private function computeMinMax() : void {
			var i:int;
			min = Number.MAX_VALUE;
			max = Number.MIN_VALUE;
			for (i = 0; i < series.length; i++) {
				if (series[i].max > max) max = series[i].max;
				if (series[i].min < min) min = series[i].min;					
			}			
		}				

	}
	
}