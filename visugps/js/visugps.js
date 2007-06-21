/*
Script: visugps.js
        Display GPS track on Google Maps

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

Copyright (c) 2007 Victor Berchet, <http://www.victorb.fr>

Credits:
    - Some code is inspired from by the Google Maps API tutorial of Mike Williams <http://www.econym.demon.co.uk/googlemaps/index.htm>
*/

/*
Class: VisuGps
        Display a GPS track on top of Google Maps
*/
var VisuGps = new Class({
    options: {
        mapDiv : 'map',
        chartDiv : 'chart',
        loadDiv : 'load',
        elevTileUrl : null,
        weatherTileUrl : null
    },
    /*
    Property: initialize
            Class constructor.
            Initialize class members and create the map.

    Arguments:
            options - an object representing VisuGps options. See Options below.

    Options:
            mapDiv - the map container
            chartDiv - the chart container
            loadDiv - an overlay displayed masking the map during initialization
            elevTileURL - list of base URLs for elevation tiles (null = no elevation map)
            weatherTileURL - list of base URLs for weather tiles (null = no weather map)
    */
    initialize : function(options) {
        this.setOptions(options);
        this.map = {};
        this.track = {};
        this.points = [];
        this.chart = {};
        this.marker = {};
        this.path = {};
        this.timer = null;
        this.infoCtrl = {};
        this.titleCtrl = {};
        this.nfo;

        if (GBrowserIsCompatible()) {
            var map = $(this.options.mapDiv);
            if (!map) return;

            // Create the map, add standard controls and keyboard handler
            this.map = new GMap2(map);
            this.map.setCenter(new GLatLng(46.73986, 2.17529), 5, G_HYBRID_MAP);
            this.map.addControl(new GMapTypeControl());
            this.map.addControl(new GLargeMapControl());
            this.map.addControl(new GScaleControl());
            this.map.enableScrollWheelZoom();
            this._createInfoControl();
            this.map.addControl(this.infoCtrl);
            this._createTitleControl('VisuGps');
            this.map.addControl(this.titleCtrl);
            new GKeyboardHandler(this.map);

            this.nfo = $('vgps-nfofield');
        }
    },
    /*
    Property: clean
            Class destructor. Help release the memory.
    */
    clean : function() {
        GUnload();
        this.chart.clean();
        window.removeEvents('resize');
    },
    /*
    Property: setTrack
            Set the track to be displayed.

    Arguments:
            track - an object representing the track. See Track below.

    Track:
            lat - Array of latitude (degre)
            lon - Array of longitude (degre)
            elev - Array of track elevation (meter)
            elevGnd - Array of ground elevation (meter)
            speed - Array of speed (km/h)
            vario - Array of climbing rate (m/s)
            time - Array of time (for x Axis labeling)
            nbPt - Number of points in lat, lon arrays.
            nbLbl - Number of label in time array
            date - Object representing the flight date
    */
    setTrack : function(track) {
        this.track = track;

        var bounds = new GLatLngBounds();

        var point = {};
        for (var i = 0; i < this.track.nbTrackPt; i++) {
            point = new GLatLng(this.track.lat[i], this.track.lon[i]);
            this.points.push(point);
            bounds.extend(point);
        }

        // Clamp values
        this.track.speed = this.track.speed.map(function(item, index) {
            return (item > this.options.maxSpeed)?this.options.maxSpeed:(item < 0)?0:item;
        }.bind(this));

        this.track.vario = this.track.vario.map(function(item, index) {
            return (item > this.options.maxVario)?this.options.maxVario:(item < -this.options.maxVario)?-this.options.maxVario:item;
        }.bind(this));

        this.track.elev = this.track.elev.map(function(item, index) {
            return (item > this.options.maxElev)?this.options.maxElev:(item < 0)?0:item;
        }.bind(this));

        // Center the map on the track
        this.map.setCenter(bounds.getCenter(), this.map.getBoundsZoomLevel(bounds));
        this._displayTrack();

        // Put the marker on the take-off place
        this.marker = new GMarker(this.points[0], {clickable:false});
        this._showMarker(0);
        this.map.addOverlay(this.marker);

        // Add event handlers
        GEvent.addListener(this.map, 'moveend', this._displayTrack.bind(this));
        GEvent.addListener(this.map, 'click', this._goNear.bind(this));
        window.addEvent('resize', this._resize.bind(this));

        var flightTitle = [this.track.date.day, this.track.date.month, this.track.date.year].join('/');

        if ((flightTitle !== '0/0/0') &&
            ($type(this.options.weatherTileUrl) === 'array')) {
            this._createModisMap(this.track.date.day, this.track.date.month, this.track.date.year);
        }

        if (this.track.pilot) flightTitle += '<br/>' + this.track.pilot;
        this.titleCtrl.setText(flightTitle);

        if ($type(this.options.elevTileUrl) === 'array') {
            this._createSrtmMap();
        }

        new RadioBtn(['btnelev', 'btnvario', 'btnspeed'], {onSelect: this._setGraph.bind(this),
                                                           classSelect: 'vgps-btn-selected'});

        // Remove the top most overlay from the map
        var load = $(this.options.loadDiv);

        if (load) {
            load.remove();
        }
    },
    /*
    Property: downloadTrack
            Load a track at the given URL.

    Arguments:
            url - URL of the track (JSON format)

    See:
            setTrack method.
    */
    downloadTrack : function(url) {
        new Json.Remote('php/vg_proxy.php?track=' + url, {onComplete: this.setTrack.bind(this)}).send();
    },
    /*
    Property: _setGraph (INTERNAL)
            Set the data to be represented on the graph

    Arguments:
            index - unused
            id - type of graph to display [btnvario, btnspeed, btnelev]
    */
    _setGraph : function(index, id) {
        if (this.chart.clean) this.chart.clean();
        delete this.chart;
        this.chart = new CChart($(this.options.chartDiv),
                                {onMouseMove : this._showMarker.bind(this),
                                 onMouseDown : this._showMarkerCenter.bind(this),
                                 onMouseWheel : this._showMarkerCenterZoom.bind(this)});
        this.chart.setGridDensity(this.track.nbChartLbl, 4);
        this.chart.setHorizontalLabels(this.track.time.label);
        this.chart.setShowLegend(true);
        switch (id) {
            case 'btnvario':
                this.chart.add('Vario', '#FF0000', this.track.vario, CHART_LINE);
                this.chart.setLabelPrecision(1);
                break;
            case 'btnspeed':
                this.chart.add('Vitesse', '#FF0000', this.track.speed, CHART_LINE);
                break;
            default:
                this.chart.add('hVol', '#FF0000', this.track.elev, CHART_LINE);
                this.chart.add('hSol', '#755545', this.track.elevGnd, CHART_AREA);
        }
        this._drawGraph();
    },
    /*
    Property: _resize (INTERNAL)
            Trigger graph redraw 100ms after the window has been resized.
            This is required for IE which keep sending resize events while the
            window is being resized.
    */
    _resize : function() {
        this.chart.showCursor(false);
        if (this.timer) $clear(this.timer);
        this.timer = this._drawGraph.delay(100, this);
    },
    /*
    Property: _drawGraph (INTERNAL)
            Draw the graph.
    */
    _drawGraph : function () {
        if (this.points.length < 5) return;
        this.chart.draw();
    },
    /*
    Property: _displayTrack (INTERNAL)
            Display the track.
    */
    _displayTrack : function() {
        if (this.points.length < 5) return;
        var path = new GPolyline(this._getReducedTrack(), "#ff0000", 1, 1);
        this.map.removeOverlay(this.path);
        this.map.addOverlay(this.path = path);
    },
    /*
    Property: _getReducedTrack (INTERNAL)
            Reduce the track point count by removing unvisible points (out of view area)
            and points that overlap.
    Return:
            The shortened track.
    */
    _getReducedTrack : function() {
        var Sw = this.map.getBounds().getSouthWest();
        var Ne = this.map.getBounds().getNorthEast();
        var deltaLat = Ne.lat() - Sw.lat();
        var deltaLng = Ne.lng() - Sw.lng();

        // Keep only points that are in the view area and close neighbourhood
        var bufSw = new GLatLng(Sw.lat() - deltaLat, Sw.lng() - deltaLng);
        var bufNe = new GLatLng(Ne.lat() + deltaLat, Ne.lng() + deltaLng);
        var scrollBuffer = new GLatLngBounds(bufSw, bufNe);

        // Flush points that are too close from each other
        var minStepLat = 3 * deltaLat / this.map.getSize().width;
        var minStepLng = 3 * deltaLng / this.map.getSize().height;

        var lastLat = this.points[0].lat();
        var lastLng = this.points[0].lng();
        var shortTrack = [];
        var point= {};
        shortTrack.push(this.points[0]);

        for (var i = 0; i < this.points.length; i ++){
            point = this.points[i];
            if (scrollBuffer.contains(point) &&
               ((Math.abs(point.lat() - lastLat) > minStepLat) ||
                (Math.abs(point.lng() - lastLng) > minStepLng))) {
                shortTrack.push(point);
                lastLat = point.lat();
                lastLng = point.lng();
            }
        }

        return shortTrack;
    },
    /*
    Property: _showMarker (INTERNAL)
            Move the marker and eventually center the map above the marker

    Arguments:
            pos - Marker location (0...1000)
            center - true to centre the map
    */
    _showMarker : function(pos, center) {
        center = $pick(center, false);
        var idx = (pos * (this.track.nbTrackPt - 1) / 1000).toInt();
        this.marker.setPoint(this.points[idx]);
        this._showInfo(pos);
        if (center) {
            this.map.panTo(this.points[idx]);
        }
    },
    /*
    Property: _showMarkerCenter (INTERNAL)
            Move the marker and center the map above the marker

    Arguments:
            pos - Marker location (GPoint object)
    */
    _showMarkerCenter : function(pos) {
        this._showMarker(pos, true);
    },
    /*
    Property: _showMarkerCenterZoom (INTERNAL)
            Move the marker, center the map above the marker and zoom

    Arguments:
            pos - Marker location (GPoint object)
            wheel - Mouse wheel direction
    */
    _showMarkerCenterZoom : function(pos, wheel) {
        if (wheel > 0) {
            this.map.zoomIn();
        } else {
            this.map.zoomOut();
        }
        this._showMarker(pos, true);
    },
    /*
    Property: _showInfo (INTERNAL)
            Update the info control with the point information

    Arguments:
            pos - point location (0...1000)
    */
    _showInfo : function(pos) {
        var idx = (pos * (this.track.nbChartPt - 1) / 1000).toInt();
        this.nfo.setHTML(this.track.elev[idx] + 'm [hV]<br/>' +
                         this.track.elevGnd[idx] + 'm [hS]<br/>' +
                         Math.max(0, (this.track.elev[idx] - this.track.elevGnd[idx])) + 'm [hR]<br/>' +
                         this.track.vario[idx] + 'm/s [Vz]<br/>' +
                         this.track.speed[idx] + 'km/h [Vx]<br/>' +
                         this._NbToStrW(this.track.time.hour[idx],2) + ':' +
                         this._NbToStrW(this.track.time.min[idx], 2) + ':' +
                         this._NbToStrW(this.track.time.sec[idx], 2) + '[Th]');
    },
    /*
    Property: _goNear (INTERNAL)
            Move the marker to the track point closest to the mouse click

    Arguments:
            marker: unused.
            point: Mouse click location (GPoint object)
    */
    _goNear : function(marker, point) {
        var bestIdx = 0;
        var bestDst = this.points[0].distanceFrom(point);
        var i, dst;
        for (i = 0; i < this.track.nbTrackPt; i++) {
            dst = this.points[i].distanceFrom(point);
            if (dst < bestDst) {
                bestIdx = i;
                bestDst = dst;
            }
        }
        this.marker.setPoint(this.points[bestIdx]);
        var pos = (1000 * bestIdx / this.track.nbTrackPt).toInt();
        this.chart.setCursor(pos);
        this._showInfo(pos);
    },
    /*
    Property: _createTitleControl (INTERNAL)
            Create the title control to display the date and pilot name.

    Note:   The control is not added to the map by this function.

    Arguments:
            title: Text displayed by the title control (changed with setText)
    */
    _createTitleControl : function(title) {
        function TitleControl(title) {
            this.div = null;
            this.title = title;
        }
        TitleControl.prototype = new GControl();

        TitleControl.prototype.initialize = function(map) {
            this.div = new Element('div', {'styles' : {'color': '#000',
                                                       'border': '1px inset #555',
                                                       'padding': '2px',
                                                       'font':'10px Verdana, Arial, sans-serif',
                                                       'marginBottom':'3px',
                                                       'background':'#FFC',
                                                       'text-align':'right',
                                                       'opacity': '0.9'}
                                  }).setHTML(this.title)
                                    .injectInside(map.getContainer());
            return this.div;
        }

        TitleControl.prototype.getDefaultPosition = function() {
            return new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(7, 30));
        }

        TitleControl.prototype.setText = function(title) {
            this.title = title;
            this.div.setHTML(title);
        }

        this.titleCtrl = new TitleControl(title);
    },
    /*
    Property: _createInfoControl (INTERNAL)
            Create the info control to display track information.

    Note:   The control is not added to the map by this function
    */
    _createInfoControl : function() {
        function InfoControl() {}

        InfoControl.prototype = new GControl();

        InfoControl.prototype.initialize = function(map) {
            var div= new Element('div', {'styles' : {'border': '1px inset #555',
                                                     'padding': '2px',
                                                     'marginBottom':'1px',
                                                     'background':'#FFC',
                                                     'opacity':'0.9',
                                                     'text-align':'right'}
                                }).setHTML('<p class="vgps-info"><strong>...iNfO</strong></p>' +
                                           '<p class="vgps-info" id="vgps-nfofield"></p>' +
                                           '<div><p class="vgps-btn" id="btnelev">h</p>' +
                                           '<p class="vgps-btn" id="btnvario">Vz</p>' +
                                           '<p class="vgps-btn" id="btnspeed">Vx</p></div>')
                                  .injectInside(map.getContainer());

            return div;
        }

        InfoControl.prototype.getDefaultPosition = function() {
            return new GControlPosition(G_ANCHOR_BOTTOM_RIGHT, new GSize(2, 12));
        }

        this.infoCtrl = new InfoControl();
    },
    /*
    Property: _createSrtmMap (INTERNAL)
            Create the SRTM (elevation) map
    */
    _createSrtmMap : function() {
        // SRTM custom map
        var srtmCpy = new GCopyright(1, new GLatLngBounds(new GLatLng(-90, -180),
                                                          new GLatLng(90, 180)),
                                                          0, "SRTM");

        var srtmCpyC = new GCopyrightCollection();
        srtmCpyC.addCopyright(srtmCpy);

        var url = this.options.elevTileUrl;
        url.map(function(item, idx) {
            return item.replace(/\/$/, '');
        });

        var srtmTL = [new GTileLayer(srtmCpyC, 0, 16)];
        srtmTL[0].getTileUrl = function(point, zoom){
                var count = url.length;
                var n = (point.x + point.y) % count;
                return 'http://' + url[n] + '/vg_tilesrtm.php?x=' + point.x + '&y=' + point.y + '&z=' + zoom;
            }
        var srtmMap = new GMapType(srtmTL, new GMercatorProjection(18), 'Elevation');

        this.map.addMapType(srtmMap);
    },
    /*
    Property: _createModisMap (INTERNAL)
            Create the MODIS (weather) map

    Arguments:
            day, month, year - date of the map

    */
    _createModisMap : function(day, month, year) {
          // Modis pictures use a flat projection
          function EuclideanProjection(){}

          EuclideanProjection.prototype=new GProjection();

          EuclideanProjection.prototype.fromLatLngToPixel=function(point, zoom){
            var size = Math.pow(2, zoom) * 256;
            var x = Math.round((point.lng() + 180) * size /360);
            var y = Math.round((90 - point.lat()) * size / 180);
            return new GPoint(x, y)
          }

          EuclideanProjection.prototype.fromPixelToLatLng=function(point, zoom, unbounded){
            var size = Math.pow(2, zoom) * 256;
            var lng = point.x * 360 / size - 180;
            var lat = 90 - (point.y * 180 / size);
            return new GLatLng(lat, lng, unbounded);
          }

          EuclideanProjection.prototype.tileCheckRange=function(tile, zoom, unbounded){
              var size = Math.pow(2, zoom);
              if (tile.y < 0 || tile.y >= size) return false;
              if (tile.x < 0 || tile.x >= size) {
                  tile.x %= size;
                  if (tile.x < 0) tile.x += size;
              }
              return true;
          }

          EuclideanProjection.prototype.getWrapWidth=function(zoom) {
            return Math.pow(2, zoom) * 256;
          }

          function getDayNumber(day, month, year) {
              var now = new Date;
              now.setUTCFullYear(year, month - 1, day);
              var ny = new Date;
              ny.setUTCFullYear(year, 0, 1);
              return (now - ny) / (1000 * 3600 * 24) + 1;
          }

          var dayNum = this._NbToStrW(getDayNumber(day, month, year), 3);
          var date = year.toString() + dayNum;

          // MODIS custom map
          var modisCpy = new GCopyright(1, new GLatLngBounds(new GLatLng(-90, -180),
                                                             new GLatLng(90, 180)),
                                                             0, "MODIS");

          var modisCpyC = new GCopyrightCollection();
          modisCpyC.addCopyright(modisCpy);

          var url = this.options.weatherTileUrl;
          url.map(function(item, idx) {
              return item.replace(/\/$/, '');
          });

          var modisTL = [new GTileLayer(modisCpyC, 0, 9)];
          modisTL[0].getTileUrl = function(point, zoom){
                var count = url.length;
                var n = (point.x + point.y) % count;
                return 'http://' + url[n] + '/vg_tilemodis.php?x=' + point.x + '&y=' + point.y + '&z=' + zoom + '&date=' + date;
              }
          var modisMap = new GMapType(modisTL, new EuclideanProjection(18), "Weather");
          this.map.addMapType(modisMap);
    },
    /*
    Property: _NbToStrW (INTERNAL)
            Return the string representation of a number (left padded with 0 to w width)

    Arguments:
            nb - number
            w - width (left padded with 0).

    Return:
            Number as string left padded with 0 to the requested width

    */
    _NbToStrW : function(nb, w) {
        var nbs = nb.toString();
        while (nbs.length < w) nbs = '0' + nbs;
        return nbs;
    }

});

VisuGps.implement(new Options);
