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
    import flash.events.Event;
    import mx.core.UIComponent;
    import mx.controls.ProgressBar;
    import mx.controls.ProgressBarLabelPlacement;
    
    /**
    * Display a mask with an indeterminate progress indicator
    * @author Victor Berchet
    */
    public class LoadingMask extends UIComponent
    {
        
        private var progress:ProgressBar;
        private static const PROGRESS_WIDTH:int = 400;
        private static const PROGRESS_HEIGHT:int = 40;
        
        public function LoadingMask() 
        {
            super();
            addEventListener(Event.RESIZE, onResize);            
            opaqueBackground = true;         
            x = y = 0;
            percentHeight = 100;
            percentWidth = 100;           
            progress = new ProgressBar();
            progress.indeterminate = true;
            progress.width = PROGRESS_WIDTH;
            progress.height = PROGRESS_HEIGHT;
            progress.setStyle("trackHeight", PROGRESS_HEIGHT);
            progress.setStyle("fontSize", PROGRESS_HEIGHT / 2);
            progress.setStyle("barColor", 0xffaa00);
            progress.label = "Loading...";
            progress.labelPlacement = ProgressBarLabelPlacement.CENTER;
            addChild(progress);            
        }
        
        private function onResize(event:Event):void {
            graphics.clear();
            graphics.lineStyle(1, 0xaaaaff);
            graphics.beginFill(0xccccff, 1);
            graphics.drawRoundRect(0, 0, 
                                   width,
                                   height, 
                                   20, 20);
            graphics.endFill();
            
            progress.x = width / 2 - PROGRESS_WIDTH / 2;
            progress.y = height / 2 - PROGRESS_HEIGHT / 2;
            
        }
        
    }
    
}