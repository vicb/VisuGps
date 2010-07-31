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
    import flash.text.TextField;
    import flash.text.TextFieldAutoSize;
    import flash.text.TextFormat;
    import flash.events.Event;
    import mx.core.UIComponent;
    import com.hexagonstar.util.debug.Debug;
    
    /**
    * Display a mask with an error message
    * @author Victor Berchet
    */
    public class ErrorMask  extends UIComponent
    {
        
        private var text:TextField;
        
        public function ErrorMask(value:String) 
        {
            super();            
            addEventListener(Event.RESIZE, onResize);            
            x = y = 0;
            percentHeight = 100;
            percentWidth = 100; 
            opaqueBackground = true;
            text = new TextField();    
            text.autoSize = TextFieldAutoSize.LEFT;
            addChild(text);
            setError(value);            
        }
        
        public function setError(value:String):void {            
            text.text = value; 
            var format:TextFormat = new TextFormat("Verdanaemb", 20);
            text.setTextFormat(format);            
            onResize(null);            
        }

        private function onResize(event:Event):void {
            text.x = (width - text.width) / 2;
            text.y = (height - text.height) / 2;
            graphics.clear();
            graphics.lineStyle(1, 0xff0000);
            graphics.beginFill(0xff0000, 0.8);
            graphics.drawRoundRect(0, 0, width, height, 20, 20);
            graphics.endFill();
        }
        
    }
    
}