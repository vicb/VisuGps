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

package fr.victorb.visugps 
{
    import com.adobe.crypto.MD5;
    import com.google.maps.controls.*;
    import com.google.maps.InfoWindowOptions;
    import com.google.maps.interfaces.IInfoWindow;
    import com.google.maps.LatLngBounds;
    import com.google.maps.MapMouseEvent;
    import com.google.maps.overlays.*;
    import com.google.maps.LatLng;
    import com.google.maps.Map;
    import com.google.maps.MapEvent;
    import com.google.maps.MapType;
    import com.google.maps.styles.StrokeStyle;
    import com.hexagonstar.util.debug.Debug;
    import flash.display.*;
    import flash.events.*;
    import flash.geom.Point;
    import flash.text.*;
    import flash.utils.Timer;
    import fr.victorb.chart.*;
    import fr.victorb.visugps.*; 
    import fr.victorb.chart.ChartEvent;
    import mx.containers.HBox;
    import mx.containers.VBox;
    import mx.containers.Panel;
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
        private var measureInfo:IInfoWindow = null;
        
        private var pilotMarker:Marker;
        
        private var track:Track;
        private var trackPoints:Array;
        
        private var infoControl:TextControl;
        private var trackControl:TextControl;
        
        private var loadingMask:LoadingMask;
        
        private var panel:Panel;
        
        private var loadTimes:int = 0;
        private var trackPoly:Polyline = null;
        
        
        /**
         * Constructor
         * @param	key Google Map key
         */
        public function VisuGps(key:String)
        {
            map = new Map();
            map.key = key;
            map.addEventListener(MapEvent.MAP_READY, onMapReady);              
        }

        /**
         * Resize the map when container has been resized
         * @param	size new size
         */
        public function setSize(size:Point):void {
            map.setSize(size);
        }
        
        /**
         * Create the panel layout and bind events
         * @param	panel
         */
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
        
        /**
         * Move the cursor to the pilot position
         * @param	value pilot position [0...1000]
         */
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
        
        /**
         * Move the pilot position when the cursor is moved on the chart
         * @param	event
         */
        private function onChartMove(event:ChartEvent):void {
            setPilotPosition(event.value);
        }
        
        /**
         * Zoom In on wheel up on the chart
         * @param	event
         */
        private function onChartWheelUp(event:ChartEvent):void {
            onChartClick(event);
            map.zoomIn();
        }

        /**
         * Zoom Out on wheel down on the chart
         * @param	event
         */
        private function onChartWheelDown(event:ChartEvent):void {
            onChartClick(event);
            map.zoomOut();
        }
        
        /**
         * Center the map on the current position when the chart is clicked
         * @param	event
         */
        private function onChartClick(event:ChartEvent):void {
            var index:int = event.value * track.getLength() / 1000;
            map.setCenter(new LatLng(track.getLat(index), track.getLon(index)));
        }
        
        
        private function doMapLayout(event:Event):void {
            map.setSize(new Point(mapHolder.width, mapHolder.height));       
        }
            
        /**
         * Handle mouse move event
         * @param	event
         */
        private function onMouseMove(event:MapMouseEvent):void {   

            if (measureLine) {
                map.removeOverlay(measureLine);
                measureLine = null;
            }
            
            var options:PolylineOptions;
                    
            options = new PolylineOptions({
                strokeStyle: new StrokeStyle({
                    color: 0xffff00,
					alpha: 0.8,
                    thickness: 3})
                });                
              
            measureLine = new Polyline(measurePoints.concat(event.latLng), options);  
            map.addOverlay(measureLine);            
			
            var iwo:InfoWindowOptions = new InfoWindowOptions({
                  strokeStyle: {
                    color: 0x0,
                    thickness: 1
                  },
                  fillStyle: {
                    color: 0xFFFFCC,
                    alpha: 0.8
                  },
                  titleFormat: new TextFormat("Verdana", 10), 
                  contentFormat: new TextFormat("Verdana", 10),
                  width: 120,
                  height: 65,
                  cornerRadius: 12,
                  padding: 2,
                  hasCloseButton: false,
                  hasTail: false,
                  tailAlign: InfoWindowOptions.ALIGN_RIGHT,
                  pointOffset: new Point(80, 40),
                  hasShadow: true
                });   
            iwo.content = getMeasureText(measurePoints.concat(event.latLng)).title + "\n" + getMeasureText(measurePoints.concat(event.latLng)).content;
            measureInfo = map.openInfoWindow(event.latLng, iwo); 
			
        }
        
        /**
         * Return the distance as text
         * @param	measurePoints Array of point for the poly to be measured
         * @return
         */
        private function getMeasureText(measurePoints:Array):Object {
            var coef:Number = 1.2;
            var trackType:String = "";
            var distance:Number = Math.round(measureLine.getLength() / 10) / 100;
            switch (measurePoints.length) {
                case 2:
                    trackType = "Distance libre";
                    coef = 1;
                break;
                case 3:
                    if (measurePoints[0].distanceFrom(measurePoints[2])  < 3000) {
                        trackType = "Aller retour";
                    } else {
                        trackType = "Distance libre 1";
                        coef = 1;
                    }
                    break;
                case 4:
                    if (measurePoints[0].distanceFrom(measurePoints[3]) < 3000) {
                        var fai:Boolean = true;
                        for (var i:int = 0; i < 3; i++) {
                            if (measurePoints[i].distanceFrom(measurePoints[i + 1]) < (distance * 1000 * 0.28)) {
                                fai = false;
                                break;
                            }
                        }
                        trackType = fai?"Triangle FAI":"Triangle plat";
                        coef = fai?1.4:1.2;
                    } else {
                        trackType = "Distance libre 2";
                        coef = 1;
                    }
                    break;
                case 5:
                    if (measurePoints[0].distanceFrom(measurePoints[4]) < 3000) {
                        trackType = "Quadrilatere";
                    }
                default:
                    trackType = "Distance";
                    coef = 0;
            }
            
        return { title: trackType,
                 content: ((distance < 1)?distance * 100 + "m":distance + "km") + (coef?"\n" + Math.round(distance * coef * 100) / 100 + "pts":"") 
               };        
        }        
        
        /**
         * Handle left click event 
         * @param	event
         */
        private function onLeftClick(event:MapMouseEvent):void {
            if (measureState == MeasureState.MEAS_ON) {
                onMouseMove(event);
                measurePoints.push(event.latLng);                
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

        /**
         * Handle the right click event
         * @param	event
         */
        private function onRightClick(event:MapMouseEvent):void {
            measureState++;
            
            switch (measureState) {
                case MeasureState.MEAS_ON:
                    measurePoints = [event.latLng];
                    measureLine = null; 
                    map.addEventListener(MapMouseEvent.MOUSE_MOVE, onMouseMove);
                break;
                
                case MeasureState.MEAS_STOP: 
                    map.removeEventListener(MapMouseEvent.MOUSE_MOVE, onMouseMove);
                break;
                
                case MeasureState.MEAS_REMOVE:
                    map.removeOverlay(measureLine);
                    measureLine = null;
                    measureState = MeasureState.MEAS_OFF;
                    map.closeInfoWindow();
                    measureInfo = null;
					Debug.trace("after remove");
                break;
                
                default:
                    measureState = MeasureState.MEAS_OFF;
            }
        }
            
        /**
         * Load the track when the map is ready
         * @param	event
         */
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
                track.load(params.trackUrl + "&load=" + loadTimes);
            } else {
                track.load("http://victorb.fr/visugps/php/vg_proxy.php?track=http://victorb.fr/track/2006-06-23.igc");
            }
            
            if (params.live == 1) {
                var timer:Timer = new Timer(2 * 60 * 1000);
                timer.addEventListener(TimerEvent.TIMER, function():void { track.load(params.trackUrl + "&load=" + loadTimes) } );                
				timer.start();
            }
        }        
               
        /**
         * Add the track to the map once loaded
         * @param	event
         */
        private function onTrackReady(event:TrackEvent):void {  
            if (loadTimes == 0) {
                loadTimes++;;
            
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
                trackControl = new TextControl(new ControlPosition(ControlPosition.ANCHOR_TOP_RIGHT, 7, 35));
                var date:Date = track.getDate();
                trackControl.text(track.getPilot() + "\n" + date.getDate() + "/" + (date.getMonth() + 1) + "/" + date.getFullYear());
                map.addControl(trackControl); 
            } else {
                charts.init();
                if (trackPoly) {
                    map.removeOverlay(trackPoly);
                    trackPoly = null;
                }
            }            
                       
            if (track.getLength() < 5) {
                panel.addChild(new ErrorMask("Invalid track format"));
                return;
            }
                        
            setPilotPosition(0);                       
            
            trackPoints = new Array();
            var point:LatLng;                        
                        
            for (var i:int = 0; i < track.getLength(); i++) {
                trackPoints.push(new LatLng(track.getLat(i), track.getLon(i)));
            }
			           
            var options:PolylineOptions;
                                  
            options = new PolylineOptions({
                strokeStyle: new StrokeStyle({
                    color: 0xFF0000,
					alpha: 1,
                    thickness: 1})
                });
            trackPoly = new Polyline(trackPoints, options);
			var bounds:LatLngBounds = trackPoly.getLatLngBounds();
            map.setCenter(bounds.getCenter(), map.getBoundsZoomLevel(bounds));						
            map.addOverlay(trackPoly);    
			
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