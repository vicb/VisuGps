/*
Script: cchart.js
        Cursor extension for chart.

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
        - This script extends a modified version of chart by WebFX.
          <http://webfx.eae.net/dhtml/chart/chart.html>

*/

/*
Class: CChart
        Extend the Chart class <http://webfx.eae.net/dhtml/chart/chart.html>
        by ading a moving cursor
*/
var CChart = Chart.extend({
    options: {
        onMouseMove: Class.empty,
        onMouseDown: Class.empty,
        onMouseWheel: Class.empty
    },
    /*
    Property: initialize
            Class constructor.

    Arguments:
            div - Chart container
            cur - Cursor container
            options - an object representing CChart options. See Options below.

    Options:
            onMouseMove - event fired on mouse move
            onMouseDown - event fired on left click
            onMouseWheel - event fire on mouse wheel change 
    */           
    initialize: function(div, cur, options) {
        this.setOptions(options);
        this.parent(div);
        this.chartDiv = $(div);
        this.cursorDiv = $(cur);
        this.position = 0;
        // Add events to both divs
        $(div).addEvent('mousemove', this._move.bindWithEvent(this));
        $(cur).addEvent('mousedown', this._down.bindWithEvent(this));
        $(div).addEvent('mousedown', this._down.bindWithEvent(this));
        $(cur).addEvent('mousewheel', this._wheel.bindWithEvent(this));
        $(div).addEvent('mousewheel', this._wheel.bindWithEvent(this));
    },
    /*
    Property: draw
            Draw the chart
    */        
    draw: function() {
        this.parent();
        var dim = this.getCoordinates();
        this.cursorDiv.setStyles({'top' : dim.top + this.chartDiv.getTop(),
                                  'height': dim.height});
    },
    /*
    Property: setCursor
            Set the cursor position
            
    Arguments:
            pos - position (0...1000)            
    */        
    setCursor: function(pos) {
        var dim = this.getCoordinates();
        var left = dim.left + this.chartDiv.getLeft();
        var x = (pos * dim.width / 1000) + left;
        this.cursorDiv.setStyle('left', x);
        this.showCursor();
    },
    /*
    Property: showCursor
            Set cursor visibity.
            
    Arguments:  
            visible - true to show to cursor (default)                       
    */    
    showCursor: function(visible) {
        visible = $pick(visible, true);
        this.cursorDiv.setStyle('visibility', visible?'visible':'hidden');
    },    
    /*
    Property: clean
            Remove events to help with memory leaks           
    */     
    clean: function() {
        this.showCursor(false);
        this.chartDiv.removeEvents();
        this.cursorDiv.removeEvents();
    },    
    /*
    Property: _move (INTERNAL)
            Set the cursor to the mouse position and fire the 'onMouseMove' event           
    
    Arguments:
            event - event
    */    
    _move: function(event) {
        var x = event.page.x;
        var dim = this.getCoordinates();
        var left = dim.left + this.chartDiv.getLeft();
        x = x < left?left:x;
        x = x > (left + dim.width)?left + dim.width:x;
        this.position = (1000 * (x - left) / dim.width).toInt();
        this.setCursor(this.position);
        this.fireEvent('onMouseMove', this.position);
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

CChart.implement(new Events, new Options);
