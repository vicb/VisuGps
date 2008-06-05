package fr.victorb.visugps 
{
    import com.adobe.crypto.MD5;
    import com.google.maps.controls.ControlPosition;
    import com.google.maps.controls.MapTypeControl;
    import com.google.maps.controls.PositionControl;
    import com.google.maps.controls.PositionControlOptions;
    import com.google.maps.controls.ZoomControl;
    import com.google.maps.LatLngBounds;
    import com.google.maps.MapMouseEvent;
    import com.google.maps.overlays.Marker;
    import com.google.maps.overlays.MarkerOptions;
    import com.google.maps.overlays.Polyline;
    import com.google.maps.overlays.PolylineOptions;
    import com.google.maps.LatLng;
    import com.google.maps.Map;
    import com.google.maps.MapEvent;
    import com.google.maps.MapType;
    import com.google.maps.styles.StrokeStyle;
    import com.hexagonstar.util.debug.Debug;
    import flash.display.Bitmap;
    import flash.display.DisplayObject;
    import flash.display.LoaderInfo;
    import flash.display.MovieClip;
    import flash.display.Sprite;
    import flash.events.MouseEvent;
    import flash.geom.Point;
    import flash.events.Event;    
    import flash.events.EventDispatcher;
    import flash.text.TextField;
    import fr.victorb.chart.Chart;
    import fr.victorb.chart.Charts;
    import fr.victorb.chart.ChartType;
    import fr.victorb.chart.Serie;
    import fr.victorb.visugps.TextControl;
    import fr.victorb.visugps.Track; 
    import fr.victorb.chart.ChartEvent;
    import mx.containers.HBox;
    import mx.containers.Panel;
    import mx.containers.VBox;
    import mx.controls.ProgressBar;
    import mx.controls.ProgressBarLabelPlacement;
    import mx.core.IUIComponent;
    import mx.core.UIComponent;

    
    public class VisuGps 
    {

        [Embed(systemFont='Verdana', fontName="Verdanaemb", mimeType="application/x-font-truetype")]
        private static const FONT_VERDANA:String;            
        
        private var map:Map;        
        
        private var layout:VBox;
        private var mapHolder:UIComponent;
        
        private var charts:Charts;
        
        private var measureState:int = MeasureState.MEAS_OFF;
        private var measurePoints:Array = new Array();
        private var measureLine:Polyline = null;
        
        private var pilotMarker:Marker;
        
        private var track:Track;
        private var trackPoints:Array;
        
        private var infoControl:TextControl;
        private var trackControl:TextControl;
        
        private var loadingMask:LoadingMask;
        
        private var panel:Panel;
        
        public function VisuGps(key:String)
        {
            map = new Map();
            map.key = key;
            map.addEventListener(MapEvent.MAP_READY, onMapReady);    
            
        }

        public function setSize(size:Point):void {
            map.setSize(size);
        }
        
        public function init(panel:Panel):void {
            layout = new VBox();
            layout.x = layout.y = 0;
            layout.percentHeight = 100;
            layout.percentWidth = 100;
            layout.setStyle("verticalGap", 0);
            panel.addChild(layout);        
            
            mapHolder = new UIComponent();
            mapHolder.addChild(map);
            mapHolder.percentHeight = 75;
            mapHolder.percentWidth = 100;
            mapHolder.addEventListener(Event.RESIZE, doMapLayout);
            map.addEventListener(MapMouseEvent.DOUBLE_CLICK, onRightClick);
            map.addEventListener(MapMouseEvent.CLICK, onLeftClick);
            layout.addChild(mapHolder);
            
            charts = new Charts();
            charts.percentHeight = 25;
            charts.percentWidth = 100;
            charts.x = 0;
            charts.y = 0;
            charts.addEventListener(ChartEvent.MOVE, onChartMove);
            charts.addEventListener(ChartEvent.WHEEL_DOWN, onChartWheelDown);
            charts.addEventListener(ChartEvent.WHEEL_UP, onChartWheelUp);
            charts.addEventListener(ChartEvent.CLICK, onChartClick);
            layout.addChild(charts);
            
            loadingMask = new LoadingMask();
            panel.addChild(loadingMask);
            
            
            this.panel = panel;
        }
        
        public function setPilotPosition(value:int):void {            
            var index:int = value * track.getLength() / 1000;
            pilotMarker.setLatLng(new LatLng(track.getLat(index), track.getLon(index)));
            index = value * track.getChartLength() / 1000;            
            infoControl.text("<b>..:iNfO</b>\n" +
                             "[hV]" + track.getElevation(index) + "m\n" + 
                             "[hS]" + track.getGroundElevation(index) + "m\n" +
                             "[hR]" + Math.max(0, track.getElevation(index) - track.getGroundElevation(index)) + "m\n" +
                             "[Vz]" + track.getVario(index) + "m/s\n" +
                             "[Vx]" + track.getSpeed(index) + "km/h\n" +
                             "[Th]" + track.getTime(index), false);            
        }
        //////////////////////////////////    
        private function onChartMove(event:ChartEvent):void {
            setPilotPosition(event.value);
        }
        
        private function onChartWheelUp(event:ChartEvent):void {
            onChartClick(event);
            map.zoomIn();
        }

        private function onChartWheelDown(event:ChartEvent):void {
            onChartClick(event);
            map.zoomOut();
        }
        
        private function onChartClick(event:ChartEvent):void {
            var index:int = event.value * track.getLength() / 1000;
            map.setCenter(new LatLng(track.getLat(index), track.getLon(index)));
        }
        
        
        private function doMapLayout(event:Event):void {
            map.setSize(new Point(mapHolder.width, mapHolder.height));
            
        }
            
        private function onMouseMove(event:MapMouseEvent):void {
            if (measureLine) {
                map.removeOverlay(measureLine);
                measureLine = null;
            }
            
            var options:PolylineOptions;
                    
            options = new PolylineOptions({
                strokeStyle: new StrokeStyle({
                    color: 0x00ff00,
                    thickness: 1})
                });
                
            
            measureLine = new Polyline(measurePoints, options);            
            
            map.addOverlay(measureLine);
            
        }
        
        private function onLeftClick(event:MapMouseEvent):void {
            Debug.trace("left");
            //if (event.ctrlKey) {
            //    onRightClick(event);
            //    return;
            //}
            if (measureState == MeasureState.MEAS_START) {
                measurePoints.push(event.latLng);
                onMouseMove(event);
            } else {
                var bestDistance:Number = trackPoints[0].distanceFrom(event.latLng);
                var bestIndex:int = 0;
                for (var i:int = 0; i < trackPoints.length; i++) {
                    if (trackPoints[i].distanceFrom(event.latLng) < bestDistance) {
                        bestDistance = trackPoints[i].distanceFrom(event.latLng);
                        bestIndex = i;
                    }
                }
                
                setPilotPosition(1000 * bestIndex / (trackPoints.length - 1));
                charts.setCursorPosition(1000 * bestIndex / (trackPoints.length - 1));
            }
        }        

        private function onRightClick(event:MapMouseEvent):void {
            Debug.trace("right");
            measureState++;
            Debug.trace("MS " + measureState);
            Debug.trace("l " + measurePoints.length);
            
            switch (measureState) {
                case MeasureState.MEAS_START:
                    measurePoints = [event.latLng];
                    measureLine = null;
                    layout.addEventListener("mouseMove", onMouseMove);
                    onMouseMove(event);
                break;
                
                case MeasureState.MEAS_STOP:
                    layout.removeEventListener("mouseMove", onMouseMove);
                
                break;
                
                case MeasureState.MEAS_REMOVE:
                    map.removeOverlay(measureLine);
                    measureLine = null;
                break;
                
                default:
                    measureState = MeasureState.MEAS_OFF;
            }
        }
            
        private function onMapReady(event:Event):void {
            map.enableScrollWheelZoom();
            map.enableContinuousZoom();
            map.addControl(new PositionControl());
            map.addControl(new MapTypeControl());
            map.addControl(new ZoomControl());
            
            map.setCenter(new LatLng(46.73986, 2.17529),
                          5,
                          MapType.PHYSICAL_MAP_TYPE);
            
            infoControl = new TextControl(new ControlPosition(ControlPosition.ANCHOR_BOTTOM_RIGHT, 7, 10));
            infoControl.text("<b>..:iNfO</b>\n" +
                             "[hV]9999m\n" + 
                             "[hV]9999m\n" +
                             "[hV]9999m\n" +
                             "[Vz]99m/s\n" +
                             "[Vx]999km/h\n" +
                             "[Th]99:99:99");
            map.addControl(infoControl);
                       
            track = new Track();
            track.addEventListener(TrackEvent.TRACK_LOADED, onTrackReady);
            
            
            var params:Object = map.root.loaderInfo.parameters;
                    
            if ("trackUrl" in params) {
                track.load(params.trackUrl)
            } else {
                track.load("http://www.victorb.fr/visugps/php/vg_proxy.php?track=http://www.victorb.fr/track/2005-05-25.igc")                                                
            }
        }        
               
        private function onTrackReady(event:TrackEvent):void {           
            panel.removeChild(loadingMask);
                        
            var markerOptions:MarkerOptions = new MarkerOptions( {
                strokeStyle: {
                    color: 0x000000
                  },
                  fillStyle: {
                    color: 0x111188,
                    alpha: 0.8
                  },
                  radius: 7,
                  hasShadow: true
                });                   

            pilotMarker = new Marker(new LatLng(0, 0), markerOptions);            
            map.addOverlay(pilotMarker);
            setPilotPosition(0);

            trackControl = new TextControl(new ControlPosition(ControlPosition.ANCHOR_TOP_RIGHT, 7, 35));
            var date:Date = track.getDate();
            trackControl.text(track.getPilot() + "\n" + date.getDay() + "/" + date.getMonth() + "/" + date.getFullYear());
            map.addControl(trackControl);            
            
            trackPoints = new Array();
            var point:LatLng;            
            var bounds:LatLngBounds = new LatLngBounds();
                        
            for (var i:int = 0; i < track.getLength(); i++) {
                point = new LatLng(track.getLat(i), track.getLon(i));
                trackPoints.push(point);
                bounds.extend(point);
            }
            
            var options:PolylineOptions;
            
            map.setCenter(bounds.getCenter(), map.getBoundsZoomLevel(bounds));
            
            options = new PolylineOptions({
                strokeStyle: new StrokeStyle({
                    color: 0xFF0000,
                    thickness: 1})
                });
            var line:Polyline = new Polyline(trackPoints, options);
            
            map.addOverlay(line);    
                    
            var chart:Chart = new Chart();
            chart.addSerie(new Serie("Elevation", track.elevation(), new ChartType(ChartType.CHART_LINE), 0xff0000));
            chart.addSerie(new Serie("Ground elevation", track.groundElevation(), new ChartType(ChartType.CHART_AREA), 0x957565));
            chart.setHorizontalLabels(track.labels());
            charts.addChart(chart);    
            chart = new Chart();
            chart.addSerie(new Serie("Vx", track.speed(), new ChartType(ChartType.CHART_LINE), 0x008800));
            chart.setHorizontalLabels(track.labels());
            charts.addChart(chart);
            chart = new Chart();
            chart.addSerie(new Serie("Vz", track.vario(), new ChartType(ChartType.CHART_LINE), 0x0000ff));
            chart.setHorizontalLabels(track.labels());
            charts.addChart(chart);
            
            charts.setChartsAlpha([1, 0.1, 0.1]);
        }

      
    }
}