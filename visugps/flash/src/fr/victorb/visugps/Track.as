package fr.victorb.visugps 
{
    import flash.net.URLLoader;
    import flash.net.URLRequest;
    import flash.net.URLRequestMethod;
    import flash.net.URLVariables;
    import flash.events.EventDispatcher;
    import flash.events.Event;
        
    import com.hexagonstar.util.debug.Debug;
    import com.adobe.serialization.json.*;
    
    /**
    * @author Victor Berchet
    */
    public class Track extends EventDispatcher
    {
        private var track : Object;
        private var callback : Function;    
        
        public function Track() : void
        {
        }        
        
        /**
         * Load a track from the specified URL
         * @param    url URL of the track
         */
        public function load(url : String) : void
        {    
            track = null;
            var request:URLRequest = new URLRequest(url);
            request.method = URLRequestMethod.GET;
            var loader : URLLoader = new URLLoader();
            loader.addEventListener(Event.COMPLETE, completeHandler);
            this.callback = callback;
            try
            {
                loader.load(request);
            }
            catch (error:Error)
            {
                dispatchEvent(new TrackEvent(TrackEvent.TRACK_LOADED)); 
            }            
            loader.addEventListener(Event.COMPLETE, completeHandler);        
        }
        
        /**
         * @return length of the track
         */
        public function getLength() : int {
            return (track.nbTrackPt is int)?track.nbTrackPt:0;
        }

        /**
         * @param    i index of the point
         * @return latitude of the specified point
         */
        public function getLat(i:int) : Number {
            return (track.lat[i] is Number)?track.lat[i]:0;
        }
        
        /**
         * @param    i index of the point
         * @return longitude of the soecified point
         */
        public function getLon(i:int) : Number {
            return (track.lon[i] is Number)?track.lon[i]:0;
        }
        
        /**
         * @return Number of points of the chart X axis
         */
        public function getChartLength() : int {
            return (track.nbChartPt is int)?track.nbChartPt:0;
        }
        
        /**
         * @return Number of labels of the chart X axis
         */
        public function getLabelLength() : int {
            return (track.nbChartLbl is int)?track.nbChartLbl:0;
        }
        
        /**
         * @return Date of the flight
         */
        public function getDate() : Date {
            if (track.date.day is int &&
                track.date.month is int &&
                track.date.year is int) {
                    return new Date(track.date.year, track.date.month, track.date.day);
                } else {
                    return new Date(2000, 1, 1);
                }
        }
        
        /**
         * @return Name of the pilot
         */
        public function getPilot() : String {
            return (track.pilot is String)?track.pilot:null;            
        }
        
        /**
         * @param    i index of the point
         * @return speed at the specified point
         */
        public function getSpeed(i:int) : Number {
            return (track.speed[i] is Number)?track.speed[i]:0;
        }
        
        /**
         * @return speed values along the track as an array
         */
        public function speed():Array { return track.speed; }        
        
        /**
         * @param    i index of the point
         * @return vario at the specified point
         */
        public function getVario(i:int) : Number {
            return (track.vario[i] is Number)?track.vario[i]:0;
        }
        
        /**
         * @return vario values along the track as an array
         */
        public function vario():Array { return track.vario; }
        
        
        /**
         * @param    i index of the point
         * @return track elevation at the specified point
         */
        public function getElevation(i:int) : int {
            return (track.elev[i] is Number)?track.elev[i]:0;
        }
        
        /**
         * @return track elevation along the track as an array
         */
        public function elevation():Array { return track.elev; }
        
        /**
         * @param    i index of the point 
         * @return ground elevation at the specified point
         */
        public function getGroundElevation(i:int) : int {
            return (track.elevGnd[i] is Number)?track.elevGnd[i]:0;
        }
        
        /**
         * @return ground elevation along the track as an array
         */
        public function groundElevation():Array { return track.elevGnd; }
        
        /**
         * @param    int i index of the label
         * @return label of the chart X axis
         */
        public function getLabel(i:int) : String {
            return (track.time.label[i] is String)?track.time.label[i]:null;
        }
        
        /**
         * @return labels for the graph x axis as an array
         */
        public function labels():Array { return track.time.label; }
        
        /**
         * @param    int i index of the point
         * @return time at the specified point
         */
        public function getTime(i:int) : String {
            if (track.time.hour[i] is Number &&
                track.time.min[i] is Number &&
                track.time.sec[i] is Number) {
                    return track.time.hour[i] + ":" + track.time.min[i] + ":" + track.time.sec[i];
                } else {
                    return null;
                }
        }

        /**
         * Function executed when the track has been loaded
         * @param    event Event triggered when the track has been loaded
         */
        private function completeHandler(event:Event):void {
            var loader:URLLoader = URLLoader(event.target);            
            var json:JSONDecoder = new JSONDecoder(loader.data);            
            track = json.getValue();
            dispatchEvent(new TrackEvent(TrackEvent.TRACK_LOADED));            
        }

    }
    
}




