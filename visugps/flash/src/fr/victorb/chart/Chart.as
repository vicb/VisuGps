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
    * Chart component
    * @author Victor Berchet
    */
    public class Chart extends UIComponent
    {
        private var _xLabelStyle:LabelStyle = new LabelStyle();
        private var _yLabelStyle:LabelStyle = new LabelStyle();
        private var series:Array = new Array();
        private var min:Number = new Number();
        private var max:Number = new Number();    
        
        private var topPadding:int = 2;
        private var bottomPadding:int = 2;
        private var leftPadding:int = 2;
        private var rightPadding:int = 2;

        private var _xMin:int;
        private var _xMax:int;
        private var _yMin:int;
        private var _yMax:int;
        private var xStep:Number;
        private var yStep:Number;
        
        private var xLabels:Array = new Array();
        private var yDensity:int = 5 ;
        
        /**
         * Constructotr
         */
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
        
        public function get xMin():int { return _xMin; }
        public function get xMax():int { return _xMax; }
        public function get yMin():int { return _yMin; }
        public function get yMax():int { return _yMax; }
        
        /**
         * Add a new serie to the graph
         * @param    serie serie to be added
         */
        public function addSerie(serie:Serie):void {
            series.push(serie);
        }
        
        /**
         * Set the alpha for the graph and its labels
         * @param    alpha alpha value
         */
        public function setAlpha(alpha:Number):void {
            this.alpha = alpha;
            for (var j:int = 0; j < numChildren; j++) {
                getChildAt(j).alpha = alpha;
            }                    
        }
        
        /**
         * Draw the chart
         */
        public function draw() : void
        {
            // Clear the graphics and remove the labels
            graphics.clear();            
            for (var j:int = 0; j < numChildren; j++) {
                if (getChildAt(j) is TextField) removeChildAt(j--);
            }
            
            if (series.length == 0) return;
            
            computeMinMax();
            
            var txt:TextField = new TextField();
            txt.text = "9999";
            txt.autoSize = TextFieldAutoSize.LEFT;

            txt.setTextFormat(new TextFormat("Verdanaemb", yLabelStyle.size));        
            _xMin = leftPadding + 8 + txt.width;
            _xMax = width - rightPadding;
            
            txt.setTextFormat(new TextFormat("Verdanaemb", xLabelStyle.size));            
            _yMin = topPadding;
            _yMax = height - bottomPadding - txt.height - 6;
            
            yStep = (_yMax - _yMin) / (max - min);
            
            drawGrid();
            drawVerticalAxis();
            drawHorizontalAxis();
            
            for (var s:int = 0; s < series.length; s++) {                
                xStep = (_xMax - _xMin) / series[s].length();                
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

        /**
         * Draw a line serie on the graph
         * @param    serie serie to be drawn
         */
        private function drawLine(serie:Serie):void {
            graphics.lineStyle(1, serie.color);
            graphics.moveTo(_xMin, _yMax - (serie.getValue(0) - min) * yStep);
            for (var i:int = 1; i < serie.length(); i++) {
                graphics.lineTo(_xMin + i * xStep, _yMax - (serie.getValue(i) - min) * yStep);
            }            
        }
        
        /**
         * Draw an area serie on the graph
         * @param    serie serie to be drawn
         */
        private function drawArea(serie:Serie):void {
            graphics.lineStyle(1, serie.color);
            graphics.moveTo(_xMin, _yMax - 1);
            graphics.beginFill(serie.color, 1);                    
            graphics.lineTo(_xMin, _yMax - (serie.getValue(0) - min) * yStep);
            for (var i:int = 1; i < serie.length(); i++) {
                graphics.lineTo(_xMin + i * xStep, _yMax - (serie.getValue(i) - min) * yStep);
            }
            graphics.lineTo(_xMax, _yMax - 1);                    
            graphics.endFill();                        
        }        
        
        /**
         * Draw the grid
         */
        private function drawGrid():void {
            graphics.lineStyle(1, 0xbbbbbb);
            
            var y:int;
            for (var i:int = 0; i < yDensity; i++) {
                y = _yMin + (max - min) * yStep * i / (yDensity - 1);
                graphics.moveTo(_xMin, y);
                graphics.lineTo(_xMax, y);                
            }
            
            var x:int;
            for (i = 0; i < xLabels.length; i++) {
                x = _xMin + (_xMax - _xMin) * i / (xLabels.length - 1);
                graphics.moveTo(x, _yMin);
                graphics.lineTo(x, _yMax);                
            }
        }
        
        /**
         * Draw the vertical axis and its labels
         */
        private function drawVerticalAxis():void {
            graphics.lineStyle(1);
            graphics.moveTo(_xMin, _yMin);
            graphics.lineTo(_xMin, _yMax);
            
            var y:int;
            var yLabel:Number = max;
            
            for (var i:int = 0; i < yDensity; i++) {
                y = _yMin + (max - min) * yStep * i / (yDensity - 1);
                
                graphics.moveTo(_xMin, y);
                graphics.lineTo(_xMin - 5, y);
                
                var txt:TextField = new TextField();
                txt.text = ((yLabel < 99)?Math.round(yLabel * 10) / 10:Math.round(yLabel)).toString();                
                txt.selectable = false;
                txt.cacheAsBitmap = true;
                txt.setTextFormat(new TextFormat("Verdanaemb", yLabelStyle.size, yLabelStyle.color));
                txt.embedFonts = true;
                txt.autoSize = TextFormatAlign.RIGHT;
                txt.x = _xMin - 8 - txt.width;
                txt.y = y - txt.height / 2;                
                addChild(txt);                
                yLabel -= (max - min) / (yDensity - 1);
            }
            
        }

        /**
         * Draw the horizontal axis and its labels
         */
        private function drawHorizontalAxis():void {
            graphics.lineStyle(1);
            graphics.moveTo(_xMin, _yMax);
            graphics.lineTo(_xMax, _yMax);
            
            var x:int;
            
            for (var i:int = 0; i < xLabels.length; i++) {
                x = _xMin + (_xMax - _xMin) * i / (xLabels.length - 1);
                graphics.moveTo(x, _yMax);
                graphics.lineTo(x, _yMax + 5);
                
                var txt:TextField = new TextField();
                txt.text = xLabels[i].toString();
                txt.selectable = false;
                txt.setTextFormat(new TextFormat("Verdanaemb", xLabelStyle.size, xLabelStyle.color));
                txt.embedFonts = true;                
                txt.autoSize = TextFormatAlign.RIGHT;
                txt.x = x - txt.width / 2;
                txt.x = Math.max(_xMin, txt.x);
                txt.x = Math.min(_xMax - txt.width, txt.x);
                txt.y = _yMax + 6;                
                addChild(txt);
            }
        }        
                
        /**
         * Compute the minimum and the maximum value of the graph
         */
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