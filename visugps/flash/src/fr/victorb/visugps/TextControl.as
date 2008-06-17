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

package fr.victorb.visugps {
import flash.events.Event;
import flash.events.MouseEvent;
import flash.geom.Point;
import flash.display.DisplayObject;
import flash.display.Sprite;
import flash.display.Shape;
import flash.text.TextField;
import flash.text.TextFieldAutoSize;
import flash.text.TextFormat;
import com.google.maps.MapEvent;
import com.google.maps.Map;
import com.google.maps.MapType;
import com.google.maps.LatLng;
import com.google.maps.LatLngBounds;
import com.google.maps.ProjectionBase;
import com.google.maps.controls.MapTypeControl;
import com.google.maps.controls.ZoomControl;
import com.google.maps.controls.PositionControl;
import com.google.maps.controls.ControlPosition;
import com.google.maps.controls.ControlBase;
import com.google.maps.interfaces.IMap;

    public class TextControl extends ControlBase {
      
        private var label:TextField = new TextField();
        private var background:Shape = new Shape();
        
        /**
        * Constructor of custom control.
        * @constructor.
        * @param controlColour  Colour transform applied to control's black buttons.
        */
        public function TextControl(position: ControlPosition) {
            // Control will be placed at the top left corner of the map,
            // 10 pixels from the edges.
            super(position);
            addChild(background);
            addChild(label);
        }
      
        public function text(text:String, size:Boolean = true):void {
            label.htmlText = text;
            label.selectable = false;
            label.autoSize = TextFieldAutoSize.LEFT;
            var format:TextFormat = new TextFormat("Verdana", 10);
            format.leftMargin = 3;
            format.rightMargin = 3;
            label.setTextFormat(format);
            
            if (size) {
                background.graphics.clear();
                background.graphics.beginFill(0xFFFFCC, 0.8);
                background.graphics.lineStyle(1, 0x000000);
                background.graphics.drawRoundRect(0, 0, label.width, label.height, 8, 8);
            }
        }
        
    }
}