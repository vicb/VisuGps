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

package fr.victorb.mobile.vgps.gps;

//#if USE_INTERNAL_GPS
//# 
//# import fr.victorb.mobile.vgps.controller.Controller;
//# import java.util.Calendar;
//# import java.util.Date;
//# import javax.microedition.location.Criteria;
//# import javax.microedition.location.Location;
//# import javax.microedition.location.LocationListener;
//# import javax.microedition.location.LocationProvider;
//# import javax.microedition.location.QualifiedCoordinates;
//# import fr.victorb.mobile.utils.Converter;
//# 
//# public class InternalGps extends Gps implements LocationListener {
//#     LocationProvider provider = null;
//#     Controller controller;
//#     GpsPosition position = new GpsPosition();
//# 
//#     public InternalGps() {
//#         try {
//#             provider = LocationProvider.getInstance(new Criteria());
//#         } catch (Exception e) {
//#         }
//#         controller = Controller.getController();
//#     }
//#        
//#     public boolean start(String config) {
//#         provider.setLocationListener(this, controller.configuration.getLogInterval(), 5, 5);
//#         return false;
//#     }
//# 
//#     public void stop() {
//#         provider.setLocationListener(null, 10, 5, 5);
//#     }
//#     
//#     public void locationUpdated(LocationProvider provider, Location location) {
//#         boolean valid = location.isValid();
//#         updatefixValid(valid);
//#         QualifiedCoordinates coordinates = location.getQualifiedCoordinates();
//#         synchronized (position) {
//#             position.latitude = Converter.degToDegMin((float) coordinates.getLatitude());
//#             position.longitude = Converter.degToDegMin((float) coordinates.getLongitude());
//#             position.elevation = (int) coordinates.getAltitude();
//#             Calendar calendar = Calendar.getInstance();
//#             calendar.setTime(new Date(location.getTimestamp()));
//#             position.time.hour = (byte) calendar.get(Calendar.HOUR_OF_DAY);
//#             position.time.minute = (byte) calendar.get(Calendar.MINUTE);
//#             position.time.second = (byte) calendar.get(Calendar.SECOND);
//#             position.date.day = (byte)calendar.get(Calendar.DAY_OF_MONTH);
//#             position.date.month = (byte)(calendar.get(Calendar.MONTH) + 1);
//#             position.date.year = (byte)(calendar.get(Calendar.YEAR) - 2000);
//#         }
//#         updatefixValid(location.isValid());
//#         updatePosition(position);         
//#     }
//#     
//#     public GpsPosition getPosition() {
//#         synchronized (position) {
//#             return position;
//#         }
//#     }    
//# 
//#     public void providerStateChanged(LocationProvider arg0, int arg1) {
//#     }
//# 
//#     public boolean UseUtcTime() {
//#         return false;
//#     }
//#     
//# }
//#endif