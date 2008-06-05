package fr.victorb.visugps 
{
    import flash.events.Event;
    import mx.core.UIComponent;
    import mx.controls.ProgressBar;
    import mx.controls.ProgressBarLabelPlacement;
    
    /**
    * ...
    * @author DefaultUser (Tools -> Custom Arguments...)
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