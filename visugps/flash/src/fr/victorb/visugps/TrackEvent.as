package fr.victorb.visugps 
{
    import flash.events.Event;
    
    /**
    * ...
    * @author DefaultUser (Tools -> Custom Arguments...)
    */
    public class TrackEvent extends Event
    {
        public static const TRACK_LOADED:String = "track_loaded";
        
        public function TrackEvent(type:String)
        {
            super(type);
        }
        
    }
    
}