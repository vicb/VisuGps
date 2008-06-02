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
	import com.google.maps.overlays.Polyline;
	import com.google.maps.overlays.PolylineOptions;
	import com.google.maps.LatLng;
	import com.google.maps.Map;
	import com.google.maps.MapEvent;
	import com.google.maps.MapType;
	import com.google.maps.styles.StrokeStyle;
	import com.hexagonstar.util.debug.Debug;
	import flash.display.DisplayObject;
	import flash.display.Sprite;
	import flash.events.MouseEvent;
	import flash.geom.Point;
	import flash.events.Event;	
	import fr.victorb.chart.Chart;
	import fr.victorb.chart.Charts;
	import fr.victorb.chart.ChartType;
	import fr.victorb.chart.Serie;
	import fr.victorb.visugps.TextControl;
	import fr.victorb.visugps.Track;
	import mx.containers.HBox;
	import mx.containers.Panel;
	import mx.containers.VBox;
	import mx.core.UIComponent;

	
	public class VisuGps 
	{
		private var map:Map;		
		
		private var layout:VBox;
		private var mapHolder:UIComponent;
		
		private var charts:Charts;
		
		private var measureState:int = MeasureState.MEAS_OFF;
		private var measurePoints:Array = new Array();
		private var measureLine:Polyline = null;
		
		public function VisuGps(key:String)
		{
			map = new Map();
			map.key = key;
			map.addEventListener(MapEvent.MAP_READY, onMapReady);	
			
		}

		public function setSize(size:Point):void {
			map.setSize(size);
			//chart.draw();
		}
		
		public function init(panel:Panel):void {
			Debug.trace( "+init");
			layout = new VBox();
			layout.percentHeight = 100;
			layout.percentWidth = 100;
			layout.setStyle("verticalGap", 0);
			panel.addChild(layout);		
			
			mapHolder = new UIComponent();
			mapHolder.addChild(map);
			mapHolder.percentHeight = 75;
			mapHolder.percentWidth = 100;
			mapHolder.addEventListener("resize", doMapLayout);
			//map.addEventListener(, onRightClick);
			//map.addEventListener("click", onLeftClick);
			layout.addChild(mapHolder);
			
			charts = new Charts();
			charts.percentHeight = 25;
			charts.percentWidth = 100;
			charts.x = 0;
			charts.y = 0;
			layout.addChild(charts);

			Debug.trace("-init");
		}
		//////////////////////////////////		
		private function doMapLayout(event:Event):void {
			Debug.trace("resize map");
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
			//	onRightClick(event);
			//	return;
			//}
			if (measureState == MeasureState.MEAS_START) {
				measurePoints.push(event.latLng);
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
			
			var myControl:TextControl = new TextControl(new ControlPosition(ControlPosition.ANCHOR_BOTTOM_RIGHT, 7, 7));
			myControl.text("a\nb\ncdefghij");
			map.addControl(myControl);
			
			
			new Track().load("http://www.victorb.fr/visugps/php/vg_proxy.php?track=http://www.victorb.fr/track/2005-05-25.igc", 
					         onTrackReady)		
		}		
		
		private function onTrackReady(track:Track):void {
			Debug.trace("++track ready");
			
			var points:Array = new Array;
			var point:LatLng;
			var bounds:LatLngBounds = new LatLngBounds();
			
			
			for (var i:int = 0; i < track.getLength(); i++) {
				point = new LatLng(track.getLat(i), track.getLon(i));
				points.push(point);
				bounds.extend(point);
			}
			var options:PolylineOptions;
			
			map.setCenter(bounds.getCenter(), map.getBoundsZoomLevel(bounds));
			
			options = new PolylineOptions({
				strokeStyle: new StrokeStyle({
					color: 0xFF0000,
					thickness: 1})
				});
			var line:Polyline = new Polyline(points, options);
			
			map.addOverlay(line);						
					
			var chart:Chart = new Chart();
			chart.addSerie(new Serie("Elevation", track.elevation(), new ChartType(ChartType.CHART_LINE), 0xff0000));
			chart.addSerie(new Serie("Ground elevation", track.groundElevation(), new ChartType(ChartType.CHART_AREA), 0x755545));
			chart.setHorizontalLabels(track.labels());
			charts.addChart(chart);	
			chart = new Chart();
			chart.addSerie(new Serie("Vx", track.speed(), new ChartType(ChartType.CHART_LINE), 0x00ff00));
			chart.setHorizontalLabels(track.labels());
			charts.addChart(chart);
			chart = new Chart();
			chart.addSerie(new Serie("Vz", track.vario(), new ChartType(ChartType.CHART_LINE), 0x0000ff));
			chart.setHorizontalLabels(track.labels());
			charts.addChart(chart);
			
			charts.setChartsAlpha([100, 10, 10]);
		}

	}
	
}