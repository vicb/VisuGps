package fr.victorb.chart 
{
    import com.hexagonstar.util.debug.Debug;
    import flash.display.Bitmap;
    import flash.display.BitmapData;
    import flash.display.Sprite;
    import flash.events.Event;
    import flash.events.MouseEvent;
    import flash.events.TimerEvent;
    import flash.geom.Point;
    import flash.utils.Timer;
    import fr.victorb.assets.AssetManager;
    import fr.victorb.component.BigThumb;
    import mx.controls.HSlider;
    import mx.controls.sliderClasses.Slider;
    import mx.core.UIComponent;
    import mx.events.SliderEvent;
    
    /**
    * Collection of Charts
    * @author Victor Berchet
    */
    public class Charts extends UIComponent
    {
        private var charts:Array = new Array();
        private var sliders:Array = new Array();
        private var cursor:Sprite;    
        
        private var iconPlayPause:Bitmap;
        private var sliderSpeed:HSlider;
        private var playTimer:Timer;
        private var playPosition:int = 0;
        
        private const SLIDER_HEIGHT:int = 25;
        private const ICON_WIDTH:int = 16;
        
        private const PLAY:int = 0;
        private const PAUSE:int = 1;
        
        private var playPauseStatus:int = PLAY;
            
        /**
         * Constructor
         */
        public function Charts() 
        {
            super();
            addEventListener(Event.RESIZE, doChartsLayout);
            addEventListener(MouseEvent.MOUSE_MOVE, onMouseMove);
            addEventListener(MouseEvent.MOUSE_WHEEL, onMouseWheel);
            addEventListener(MouseEvent.CLICK, onMouseClick);
            
            iconPlayPause = new AssetManager.ICON_PLAY() as Bitmap;
            addChild(iconPlayPause);
            iconPlayPause.x = 0;
            iconPlayPause.y = (SLIDER_HEIGHT - iconPlayPause.height) / 2; 
            
            sliderSpeed = new HSlider;
            sliderSpeed.y = SLIDER_HEIGHT / 2;
            sliderSpeed.x = 0;
            sliderSpeed.minimum = 0;
            sliderSpeed.maximum = 99;
            sliderSpeed.liveDragging = true;
            sliderSpeed.sliderThumbClass = BigThumb;
            sliderSpeed.setStyle("fillColors", [ 0xFFFFFF, 0]);
            sliderSpeed.dataTipFormatFunction = function(value:int):String { return "Speed : " + value; };
            sliderSpeed.addEventListener(Event.CHANGE, onSliderSpeedChange);   
            addChild(sliderSpeed);            
        }
        
        /**
         * Add a chart to the collection
         * @param	chart chart to be added
         */
        public function addChart(chart:Chart):void {
            charts.push(chart);
            addChild(chart);
            chart.x = 0;
            chart.y = SLIDER_HEIGHT;
            var slider:HSlider = new HSlider();
            slider.y = SLIDER_HEIGHT / 2;
            slider.x = 0;
            slider.minimum = 0;
            slider.maximum = 100;
            slider.liveDragging = true;
            slider.sliderThumbClass = BigThumb;
            slider.setStyle("fillColors", [ 0xFFFFFF, chart.getColor()]);
            slider.dataTipFormatFunction = function(value:int):String {return chart.getName() + " : " + value};
            slider.addEventListener(Event.CHANGE, onSliderChange);        
            sliders.push(slider);
            addChild(slider);
        }
        
        /**
         * Set the chart alphas and coreesponding slider values
         * @param	values Array of alphas for each of the serie
         */
        public function setChartsAlpha(values:Array):void {
            for (var i:int = 0; i < values.length; i++) {
                charts[i].setAlpha(values[i]);
                sliders[i].setThumbValueAt(0, values[i] * 100);
            }        
            doChartsLayout(null);
        }
        
        /**
         * Set the cursor horizontal position
         * @param	value cursor position (0...999)
         */
        public function setCursorPosition(value:int):void {
            if (cursor) {
                cursor.x = charts[0].xMin + (charts[0].xMax - charts[0].xMin) * value / 1000;            
            }
        }
        
        /**
         * Start or stop the cursor animation
         * @param	event
         */
        private function onPlayPause(event:MouseEvent):void {
            removeChild(iconPlayPause)
            if (playPauseStatus == PLAY) {
                iconPlayPause = new AssetManager.ICON_PAUSE() as Bitmap;
                playPauseStatus = PAUSE;                
                playTimer = new Timer(100 - sliderSpeed.value);
                playTimer.start();
                playTimer.addEventListener(TimerEvent.TIMER, onPlayTick);                
            } else {
                iconPlayPause = new AssetManager.ICON_PLAY() as Bitmap;
                playPauseStatus = PLAY;
                playTimer.stop();
            }
            addChild(iconPlayPause);
            iconPlayPause.x = 0;
            iconPlayPause.y = (SLIDER_HEIGHT - iconPlayPause.height) / 2; 
        }
        
        /**
         * Set the animation speed (when the slider is moved)
         * @param	event
         */
        private function onSliderSpeedChange(event:SliderEvent):void {
            playTimer.delay = 100 - event.value;
        }
        
        /**
         * Handle animation ticks by moving the cursor and dispatching the MOVE event
         * @param	event
         */
        private function onPlayTick(event:TimerEvent):void {
            if (++playPosition == 1000) {
                playPosition = 0;
                onPlayPause(null);
            } else {
                setCursorPosition(playPosition);
                var chartEvent:ChartEvent = new ChartEvent(ChartEvent.MOVE,
                                                           (cursor.x - charts[0].xMin) * 1000 / (charts[0].xMax - charts[0].xMin));
                dispatchEvent(chartEvent);                   
            }
        }
        
        /**
         * Dispatch an event when the mouse click occur over a chart
         * @param	event
         */
        private function onMouseClick(event:MouseEvent):void {
            Debug.trace("mouse click");
            Debug.trace(event.target.name);
            Debug.trace(globalToContent(new Point(event.stageX, event.stageY)).x);
            Debug.trace(globalToContent(new Point(event.stageX, event.stageY)).y);
            if (globalToContent(new Point(event.stageX, event.stageY)).y < SLIDER_HEIGHT) {
                if (globalToContent(new Point(event.stageX, event.stageY)).x < ICON_WIDTH) onPlayPause(event);
                return;
            }
            if (cursor) {
                var chartEvent:ChartEvent = new ChartEvent(ChartEvent.CLICK,
                                                          (cursor.x - charts[0].xMin) * 1000 / (charts[0].xMax - charts[0].xMin));
                dispatchEvent(chartEvent);                    
            }
        }

        /**
         * Dispatch an event when the mouse move over a chart
         * @param	event
         */        
        private function onMouseMove(event:MouseEvent):void {
            if (globalToContent(new Point(event.stageX, event.stageY)).y < SLIDER_HEIGHT) return;            
            if (cursor &&
                event.stageX >= charts[0].xMin &&
                event.stageX <= charts[0].xMax) {
                    cursor.x = event.stageX;
                    cursor.y = 0;
                    var chartEvent:ChartEvent = new ChartEvent(ChartEvent.MOVE,
                                                               (cursor.x - charts[0].xMin) * 999 / (charts[0].xMax - charts[0].xMin));
                    dispatchEvent(chartEvent);                    
                }
        }

        /**
         * Dispatch an event when the mouse wheel move over a chart
         * @param	event
         */         
        private function onMouseWheel(event:MouseEvent):void {
            if (globalToContent(new Point(event.stageX, event.stageY)).y < SLIDER_HEIGHT) return;
            if (cursor) {
                var chartEvent:ChartEvent;
                if (event.delta < 0) {
                    chartEvent= new ChartEvent(ChartEvent.WHEEL_DOWN,
                                               (cursor.x - charts[0].xMin) * 1000 / (charts[0].xMax - charts[0].xMin));
                } else {
                    chartEvent = new ChartEvent(ChartEvent.WHEEL_UP,
                                                (cursor.x - charts[0].xMin) * 1000 / (charts[0].xMax - charts[0].xMin));               
                }
            dispatchEvent(chartEvent);                            
            }
        }        
        
        /**
         * Update the layout when the component is resized
         * @param	event
         */
        private function doChartsLayout(event:Event):void {    
            if (charts.length == 0) return
            
            // Mouse events do not seem to always trigger without background
            graphics.clear();
            graphics.beginFill(0xeeeeee, 0.1);
            graphics.drawRect(0, 0, width, height);
            graphics.endFill();
            
            var sliderWidth:int = (width - ICON_WIDTH) / (charts.length + 1);
            
            sliderSpeed.x = ICON_WIDTH;
            sliderSpeed.width = sliderWidth;
            
            for (var i:int = 0; i < charts.length; i++) {
                charts[i].width = width;
                charts[i].height = height - SLIDER_HEIGHT;
                sliders[i].x = (i + 1) * sliderWidth + ICON_WIDTH;
                sliders[i].width = sliderWidth;
                charts[i].draw();
            }
            
            if (cursor) removeChild(cursor);
            
            cursor = new Sprite();
            cursor.graphics.lineStyle(2, 0xffcc00);            
            cursor.graphics.moveTo(0, charts[0].yMin + SLIDER_HEIGHT);
            cursor.graphics.lineTo(0, charts[0].yMax + SLIDER_HEIGHT);            
            addChild(cursor);
        }
        
        /**
         * Update chart alphas when the slider are changed
         * @param	event
         */
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