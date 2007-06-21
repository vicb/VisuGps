/*----------------------------------------------------------------------------\
|                                  Chart 1.0                                  |
|                            Canvas Chart Painter                             |
|-----------------------------------------------------------------------------|
|                          Created by Emil A Eklund                           |
|                        (http://eae.net/contact/emil)                        |
|-----------------------------------------------------------------------------|
| Canvas implementation of the chart painter API. A canvas element is used to |
| draw the chart,  html elements are used for the legend and  axis labels as, |
| at the time being, there is no text support in canvas.                      |
|-----------------------------------------------------------------------------|
|                      Copyright (c) 2006 Emil A Eklund                       |
|- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -|
| This program is  free software;  you can redistribute  it and/or  modify it |
| under the terms of the MIT License.                                         |
|- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -|
| Permission  is hereby granted,  free of charge, to  any person  obtaining a |
| copy of this software and associated documentation files (the "Software"),  |
| to deal in the  Software without restriction,  including without limitation |
| the  rights to use, copy, modify,  merge, publish, distribute,  sublicense, |
| and/or  sell copies  of the  Software, and to  permit persons to  whom  the |
| Software is  furnished  to do  so, subject  to  the  following  conditions: |
| The above copyright notice and this  permission notice shall be included in |
| all copies or substantial portions of the Software.                         |
|- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -|
| THE SOFTWARE IS PROVIDED "AS IS",  WITHOUT WARRANTY OF ANY KIND, EXPRESS OR |
| IMPLIED,  INCLUDING BUT NOT LIMITED TO  THE WARRANTIES  OF MERCHANTABILITY, |
| FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE |
| AUTHORS OR  COPYRIGHT  HOLDERS BE  LIABLE FOR  ANY CLAIM,  DAMAGES OR OTHER |
| LIABILITY, WHETHER  IN AN  ACTION OF CONTRACT, TORT OR  OTHERWISE,  ARISING |
| FROM,  OUT OF OR  IN  CONNECTION  WITH  THE  SOFTWARE OR THE  USE OR  OTHER |
| DEALINGS IN THE SOFTWARE.                                                   |
|- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -|
|                         http://eae.net/license/mit                          |
|-----------------------------------------------------------------------------|
| 2006-01-03 | Work started.                                                  |
| 2006-01-05 | Added legend and axis labels. Changed the painter api slightly |
|            | to allow two-stage initialization (required for ie/canvas) and |
|            | added legend/axis related methods. Also updated bar chart type |
|            | and added a few options, mostly related to bar charts.         |
| 2006-01-07 | Updated chart size calculations to take legend and axis labels |
|            | into consideration.  Split painter implementations to separate |
|            | files.                                                         |
| 2006-04-16 | Updated to use the  ExplorerCanvas ie emulation  layer instead |
|            | of the, now deprecated, IECanvas one.                          |
|-----------------------------------------------------------------------------|
| Created 2006-01-03 | All changes are in the log above. | Updated 2006-04-16 |
\----------------------------------------------------------------------------*/

function CanvasChartPainterFactory() {
    return new CanvasChartPainter();
}

function CanvasChartPainter() {
    this.base = AbstractChartPainter;
};

CanvasChartPainter.prototype = new AbstractChartPainter;

CanvasChartPainter.prototype.create = function(el) {
    this.el = el = $(el);
    el.empty();

    var dim = el.getCoordinates();
    this.w = dim.width;
    this.h = dim.height;

    this.canvas = new Element('canvas', {'styles' : { 'width' : this.w,
                                                      'height' : this.h}
                                        }).injectInside(el);
    this.canvas.width  = this.w;
    this.canvas.height = this.h;

    /* Init explorercanvas emulation for IE */
    if ((!this.canvas.getContext) && (typeof G_vmlCanvasManager != "undefined")) {
        this.canvas = G_vmlCanvasManager.initElement(this.canvas);
    }
};

CanvasChartPainter.prototype.init = function(xlen, ymin, ymax, xgd, ygd, bLegendLabels) {
    this.ctx = this.canvas.getContext('2d');

    this.chartx = 0;
    this.chartw = this.w;
    this.charth = this.h;
    this.charty = 0;

    this.xlen = xlen;
    this.ymin = ymin;
    this.ymax = ymax;
    this.xgd  = xgd;
    this.ygd  = ygd;

    this.calc(this.chartw, this.charth, xlen, ymin, ymax, xgd, ygd);
};

CanvasChartPainter.prototype.drawLegend = function(series) {
    var legend, list, item, label;

    legend = new Element('div', {'styles' : {'position' : 'absolute',
                                             'right': 0}
                                }).addClass('legend');

    list = new Element('ul');

    for (i = 0; i < series.length; i++) {
        item = new Element('li', {'styles': {'color' : series[i].color}});
        label = new Element('span', {'styles' : {'color' : 'black'}})
                    .appendText(series[i].label).injectInside(item);
        item.injectInside(list);
    }
    list.injectInside(legend.injectInside(this.el));

    this.legend = legend.setStyle('top', this.charty + (this.charth / 2) - (legend.offsetHeight / 2));

    /* Recalculate chart width and position based on labels and legend */
    this.chartw = this.w - (this.legend.offsetWidth + 5);
    
    this.calc(this.chartw, this.charth, this.xlen, this.ymin, this.ymax, this.xgd, this.ygd);
};

CanvasChartPainter.prototype.drawVerticalAxis = function(ygd, precision, labelPos) {
    var axis, item, step, y, ty, n, yoffset, value, multiplier, w, items, pos;

    var xLblIn = (labelPos.x == 'in');
    var yLblIn = (labelPos.y == 'in');

    /* Calculate step size and rounding precision */
    multiplier = Math.pow(10, precision);
    step       = this.range / (ygd - 1);

    /* Create container */
    axis = new Element('div', {'styles' : {'position' : 'absolute',
                                           'left' : yLblIn?7:0,
                                           'top' : 0,
                                           'textAlign' : 'right'}
                              }).injectInside(this.el);

    /* Draw labels and points */
    this.ctx.fillStyle = 'black';
    w = 0;
    items = [];
    for (n = 0, i = this.ymax; (i > this.ymin) && (n < ygd - 1); i -= step, n++) {
        item = new Element('span');
        value = parseInt(i * multiplier) / multiplier;
        item.appendText(value).injectInside(axis);
        items.push([i, item]);
        w = Math.max(w, item.offsetWidth);
    }

    /* Draw last label and point (lower left corner of chart) */
    item = new Element('span');
    value = parseInt(i * multiplier) / multiplier;
    item.appendText(this.ymin).injectInside(axis);
    items.push([this.ymin, item]);
    w = Math.max(w, item.offsetWidth);

    /* Set width of container to width of widest label */
    axis.setStyle('width' ,w);

    /* Recalculate chart width and position based on labels and legend */
    var lblWidth = yLblIn?5:w + 5;
    var lblHeight = xLblIn?Math.max(item.offsetHeight / 2,5):item.offsetHeight + 5;
    this.chartx = lblWidth;
    this.charty = item.offsetHeight / 2;
    this.charth = this.h - (lblHeight + this.charty);
    this.chartw = this.w - ((this.legend?this.legend.offsetWidth:0) + 5 + lblWidth);
    this.calc(this.chartw, this.charth, this.xlen, this.ymin, this.ymax, this.xgd, this.ygd);

    /* Position labels on the axis */
    n       = this.range / this.charth;
    yoffset = this.ymin / n;
    for (i = 0; i < items.length; i++) {
        item = items[i][1];
        pos = items[i][0];
        if (pos == this.ymin) { 
            y = this.charty + this.charth - 1; 
        } else { 
            y = this.charty + (this.charth - (pos / n) + yoffset);
        }
        this.ctx.fillRect(this.chartx - 5, y, 5, 1);
        ty = y - (item.offsetHeight / 2);
        item.setStyles({'position' : 'absolute',
                        'top' : ty});
        if (yLblIn) {
            item.setStyle('left', 0);
        } else {
            item.setStyle('right', 0);
        }
    }
};


CanvasChartPainter.prototype.drawHorizontalAxis = function(xlen, labels, xgd, precision, labelPos) {
    var axis, item, step, x, tx, n, multiplier;

    var xLblIn = (labelPos.x == 'in');

    /* Calculate offset, step size and rounding precision */
    multiplier = Math.pow(10, precision);
    n          = this.chartw / (xgd - 1);

    /* Create container */
    axis = new Element('div', {'styles': {'position' : 'absolute',
                                          'left' : 0,
                                          'top' : this.charty + this.charth + 5,
                                          'width' : this.w }
                              }).injectInside(this.el);

    /* Draw labels and points */
    this.ctx.fillStyle = 'black';
    for (i = 0; i < xgd; i++) {
        item = new Element('span')
        item.appendText(labels[i]).injectInside(axis);
        x = this.chartx + (n * i);
        tx = x - (item.offsetWidth / 2);
        tx = Math.max(tx, this.chartx);
        tx = Math.min(tx, this.chartx + this.chartw - item.offsetWidth)
        item.setStyles({'position' : 'absolute',
                        'left' : tx,
                        'top' : '0px'});
        this.ctx.fillRect(x, this.charty + this.charth, 1, 5);
    }

    if (xLblIn) {
        axis.setStyle('top', this.charty + this.charth - item.offsetHeight);
    }
};


CanvasChartPainter.prototype.drawAxis = function() {
    this.ctx.fillStyle = 'black';
    this.ctx.fillRect(this.chartx, this.charty, 1, this.charth-1);
    this.ctx.fillRect(this.chartx, this.charty + this.charth - 1, this.chartw+1, 1);
};


CanvasChartPainter.prototype.drawBackground = function() {
    this.ctx.fillStyle = 'white';
    this.ctx.fillRect(0, 0, this.w, this.h);
};


CanvasChartPainter.prototype.drawChart = function() {
    this.ctx.fillStyle = 'silver';
    if (this.xgrid) {
        for (i = this.xgrid; i < this.chartw; i += this.xgrid) {
            this.ctx.fillRect(this.chartx + i, this.charty, 1, this.charth-1);
    }   }
    if (this.ygrid) {
        for (i = this.charth - this.ygrid; i > 0; i -= this.ygrid) {
            this.ctx.fillRect(this.chartx + 1, this.charty + i, this.chartw, 1);
}   }   };


CanvasChartPainter.prototype.drawArea = function(color, values) {
    var i, len, x, y, n, yoffset;

    /* Determine distance between points and offset */
    n = this.range / this.charth;
    yoffset = (this.ymin / n);

    len = values.length;
    if (len) {
        this.ctx.fillStyle = color;

        /* Begin line in lower left corner */
        x = this.chartx + 1;
        this.ctx.beginPath();
        this.ctx.moveTo(x, this.charty + this.charth - 1);

        /* Determine position of first point and draw it */
        y = this.charty + this.charth - (values[0] / n) + yoffset;
        this.ctx.lineTo(x, y);

        /* Draw lines to succeeding points */
        for (i = 1; i < len; i++) {
            y = this.charty + this.charth - (values[i] / n) + yoffset;
            x += this.xstep;
            this.ctx.lineTo(x, y);
        }

        /* Close path and fill it */
        this.ctx.lineTo(x, this.charty + this.charth - 1);
        this.ctx.closePath();
        this.ctx.fill();
}   };


CanvasChartPainter.prototype.drawLine = function(color, values) {
    var i, len, x, y, n, yoffset;

    /* Determine distance between points and offset */
    n = this.range / this.charth;
    yoffset = (this.ymin / n);

    len = values.length;
    if (len) {
        this.ctx.lineWidth   = 1;
        this.ctx.strokeStyle = color;

        /* Determine position of first point and draw it */
        x = this.chartx + 1;
        y = this.charty + this.charth - (values[0] / n) + yoffset;
        this.ctx.beginPath();
        this.ctx.moveTo(x, y);

        /* Draw lines to succeeding points */
        for (i = 1; i < len; i++) {
            y = this.charty + this.charth - (values[i] / n) + yoffset;
            x += this.xstep;
            this.ctx.lineTo(x, y);
        }

        /* Stroke path */
        this.ctx.stroke();
}   };


CanvasChartPainter.prototype.drawBars = function(color, values, xlen, xoffset, width) {
    var i, len, x, y, n, yoffset;

    /* Determine distance between points and offset */
    n = this.range / this.charth;
    yoffset = (this.ymin / n);

    len = values.length;
    if (len > xlen) { len = xlen; }
    if (len) {
        this.ctx.fillStyle = color;

        /* Determine position of each bar and draw it */
        x = this.chartx + xoffset + 1;
        for (i = 0; i < len; i++) {
            y = this.charty + this.charth - (values[i] / n) + yoffset;

            this.ctx.beginPath();
            this.ctx.moveTo(x, this.charty + this.charth-1);
            this.ctx.lineTo(x, y );
            this.ctx.lineTo(x+width, y);
            this.ctx.lineTo(x+width, this.charty + this.charth-1);
            this.ctx.closePath();
            this.ctx.fill();

            x += this.xstep;
}   }   };

CanvasChartPainter.prototype.getCoordinates = function() {
    return {left:this.chartx, 
            top:this.charty, 
            width:this.chartw, 
            height:this.charth};
};
