/*
Script: charts.js
        Display mulitple overlapped charts with programmable opacity and z-order

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
        - This script uses a modified version of chart by WebFX.
          <http://webfx.eae.net/dhtml/chart/chart.html>

*/

/*
Class: Charts
       Overlap multiple charts with programmable opacity and z-order
*/
var Charts = new Class({
    Implements: [Events, Options],
    options: {
        cursor : true,
        keySupport : true,
        onMouseMove: $empty,
        onMouseDown: $empty,
        onMouseWheel: $empty
    },
    /*
    Property: initialize
            Class constructor.

    Arguments:
            div - Chart container
            options - an object representing Charts options. See Options below.

    Options:
            cursor - true to show the cursor
            keySupport - true to enable key (left/right) support
            onMouseMove - event fired on mouse move
            onMouseDown - event fired on left click
            onMouseWheel - event fire on mouse wheel change
    */
    initialize: function(div, options) {
        this.setOptions(options);
        this.chartDiv = $(div);
        this.charts = [];
        this.sortable = null;
        this.curPos = 0;
        this.position = 0;
        
        if (this.options.cursor) {
            this.cursorDiv = new Element('div', {'styles' : { 'position' : 'absolute',
                                                              'border-left' : 'dashed 1px #508',
                                                              'width' : 0,
                                                              'z-index' : 80,
                                                              'visibility' : 'hidden'}
                                                }
                                        ).inject($(div))
                                         .addEvents({'mousedown' : this._down.bindWithEvent(this),
                                                     'mousewheel' : this._wheel.bindWithEvent(this)});
        }
        
        $(div).addEvents({'mousemove' : this._move.bindWithEvent(this),
                          'mousedown' : this._down.bindWithEvent(this),
                          'mousewheel' : this._wheel.bindWithEvent(this)});

        if (this.options.keySupport) document.addEvent('keydown', this._move.bindWithEvent(this));

        function stopEvent(event, notOnSort) {
          new Event(event).stop();
        };
        
        this.sliderPanel = new Element('div', {'events': {'mousedown' : stopEvent,
                                                          'mousewheel' : stopEvent},
                                               'id' : 'vgps-sliderPanel'
                                              }).inject($(div));

        var handle = new Element('div', {'id' : 'sliderPanel-handle'}).inject(this.sliderPanel);
        this.sliders = new Element('div').inject(this.sliderPanel);
        
        this.sliderPanel.makeDraggable({'handle': handle, 'container' : this.chartDiv});
                                              
    },
    /*
    Property: draw
            Draw the charts
    */
    draw: function() {
        if (this.charts.length) {
            this.charts.each(function(chart) {
                chart.draw();
            });
            if (!this.sortable) {
              function stopSort() {
                    var order = this.sortable.serialize();
                    order.each(function(item, index) {
                        $(item.replace('slider', 'chart')).setStyle('z-index', 20 - index);
                    });
                };

              this.sortable = new Sortables(this.sliders, {'handle': '.handle',
                                                           'onComplete': stopSort.bind(this),
                                                           'constrain': this.sliders
                                                          });
            }
            if (this.options.cursor) {
                var dim = this.charts[0].getCoordinates();
                this.cursorDiv.setStyles({'top' : dim.top,
                                          'height': dim.height});
            }
        }
    },
    /*
    Property: add
            Add a graph to the collection

    Arguments:
            label - slider label
            opacity - initial graph opacity [0..1]
            color - slider color
            options - chart options (see chart.js)
            
    Return:
            The chart object (see chart.js)
    */
    add: function(label, opacity, color, options) {
        label = $pick(label, 'label');
        opacity = $pick(opacity.limit(0, 1), 1);
        color = $pick(color, '#f00');

        function setOpacity(opacity) {
            div.setStyle('opacity', opacity / 100);
        }

        var idNum = this.charts.length;

        var div = new Element('div', {'class': 'chart vgps-chart',
                                      'styles' : {'opacity' : opacity},
                                      'id' : 'chart-' + idNum})
                             .inject(this.chartDiv, 'top');
                             
        var chart = new Chart(div, $pick(options, {}));

        this.charts.push(chart);
        
        new Element('table', {'id': 'slider-' + idNum}).adopt(
            new Element('tbody').adopt(
                new Element('tr').adopt(
                    [new Element('td', {'class': 'handle',
                                                 'id': 'hanlde-' + idNum,
                                                 'html': label}),
                     new Element('td', {'class': 'bar',
                                        'id': 'bar-' + idNum})]))).inject(this.sliders);
                                        
        new SliderProgress($('bar-' + idNum), {'color': color,
                                               'onChange': setOpacity}).set(opacity * 100);

        return chart;
    },
    /*
    Property: setCursor
            Set the cursor position

    Arguments:
            pos - position (0...1000)
    */
    setCursor: function(pos) {
        if (this.options.cursor && this.charts.length) {
            var dim = this.charts[0].getCoordinates();
            this.position = pos = pos.limit(0, 1000);
            var left = dim.left + this.chartDiv.getPosition().x;
            var x = (pos * dim.width / 1000) + left;
            this.cursorDiv.setStyle('left', x);
            this.showCursor();
        }
    },
    /*
    Property: showCursor
            Set cursor visibity.

    Arguments:
            visible - true to show to cursor (default)
    */
    showCursor: function(visible) {
        if (this.options.cursor) {
            visible = $pick(visible, true);
            this.cursorDiv.setStyle('visibility', visible?'visible':'hidden');
        }
    },
    /*
    Property: clean
            Remove events to help with memory leaks
    */
    clean: function() {
        this.showCursor(false);
        this.chartDiv.empty();
    },
    /*
    Property: _move (INTERNAL)
            Set the cursor to the mouse position and fire the 'onMouseMove' event

    Arguments:
            event - event
    */
    _move: function(event) {
        if (this.charts.length) {
            var pos = this.position;
            if (event.type.contains('key')) {
                var offset = 0;
                if (event.key === 'left') offset = -1;
                if (event.key === 'right') offset = 1;
                if (offset != 0) event.stop();
                offset = event.shift?10 * offset:offset;
                pos += offset;
            } else {
                var x = event.page.x;
                var dim = this.charts[0].getCoordinates();
                var left = dim.left + this.chartDiv.getPosition().x;
                x = x < left?left:x;
                x = x > (left + dim.width)?left + dim.width:x;
                pos = (1000 * (x - left) / dim.width).toInt();
            }
            pos = pos.limit(0, 1000);
            this.setCursor(pos);
            this.fireEvent('onMouseMove', pos);
        }
    },
    /*
    Property: _down (INTERNAL)
            Fire the 'onMouseDown' event
    */
    _down: function(event) {
        this.fireEvent('onMouseDown', this.position);
    },
    /*
    Property: _wheel (INTERNAL)
            Fire the 'onMouseWheel' event
    */
    _wheel: function(event) {
        this.fireEvent('onMouseWheel', [this.position, event.wheel]);
    }
});
