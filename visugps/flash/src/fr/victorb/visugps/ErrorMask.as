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
            Debug.trace("Error resize");
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