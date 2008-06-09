package fr.victorb.visugps 
{
    
    /**
    * State of the measurment SM
    * @author Victor Berchet
    */
    public class MeasureState 
    {
        static public const MEAS_OFF:int = 0;
        static public const MEAS_ON:int = 1;
        static public const MEAS_STOP:int = 2 ;
        static public const MEAS_REMOVE:int = 3;        
        
        public function MeasureState() 
        {
            
        }
    }
    
}