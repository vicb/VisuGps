/*
 * hexagon framework - Multi-Purpose ActionScript 3 Framework.
 * Copyright (C) 2007 Hexagon Star Softworks
 *       __    __
 *    __/  \__/  \__    __
 *   /  \__/HEXAGON \__/  \
 *   \__/  \__/ FRAMEWORK_/
 *            \__/  \__/
 *
 * ``The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */
package com.hexagonstar.util.debug
{
	import flash.display.Stage;
	import flash.events.Event;
	import flash.events.NetStatusEvent;
	import flash.events.StatusEvent;
	import flash.net.LocalConnection;
	import flash.net.SharedObject;
	import flash.net.SharedObjectFlushStatus;
	import flash.system.Security;
	import flash.system.SecurityPanel;
	import flash.system.System;
	import flash.utils.ByteArray;
	
	/**
	 * Alcon Debug class (AS 3.0 version)
	 * Sends trace actions to the Alcon output panel through a local connection.
	 * 
	 * @version 2.0.1 (2007.03.26)
	 */
	public final class Debug
	{
		// Constants //////////////////////////////////////////////////////////////////
		
		public static const LEVEL_DEBUG:uint	= 0;
		public static const LEVEL_INFO:uint	= 1;
		public static const LEVEL_WARN:uint	= 2;
		public static const LEVEL_ERROR:uint	= 3;
		public static const LEVEL_FATAL:uint	= 4;
		
		// Properties /////////////////////////////////////////////////////////////////
		
		private static var _fpsPollInterval:uint	= 1000;
		private static var _filterLevel:uint		= 0;
		
		private static var _isConnected:Boolean	= false;
		private static var _isPollingFPS:Boolean	= false;
		private static var _isDisabled:Boolean	= false;
		private static var _isLargeData:Boolean	= false;
		
		private static var _connection:LocalConnection;
		private static var _stopWatch:StopWatch;
		private static var _fpsMeter:FPSMeter;
		private static var _stage:Stage;
		
		// Constructor ////////////////////////////////////////////////////////////////
		
		/**
		 * Internal Constructor
		 */
		function Debug()
		{
		}
		
		// Public Methods /////////////////////////////////////////////////////////////
		
		/**
		 * The trace method accepts three arguments, the first contains the data which
		 * is going to be traced, the second if of type Boolean is used to indicate
		 * recursive object tracing mode, if of type int desribes the filtering level
		 * for the output.
		 * 
		 * @param arg0 The object to be traced (defaults to undefined).
		 * @param arg1 True if recursive object tracing, optional (defaults to null).
		 * @param arg2 Output level, optional, defaults to 1.
		 */
		public static function trace(arg0:* = undefined, arg1:* = null, arg2:int = -1):void
		{
			var data:* = arg0;
			var recursive:Boolean = false;
			var level:int = 1;
			
			// Check if argument 1 is a boolean or a number:
			if (typeof(arg1) == "boolean")
			{
				recursive = arg1;
			}
			else if (typeof(arg1) == "number")
			{
				level = arg1;
			}
			if (arg2 > -1)
			{
				level = arg2;
			}
			
			// Only show messages equal or higher than current filter level:
			if (level >= _filterLevel && level < 7)
			{
				// Send the data to the output console:
				send("onData", data, level, ((recursive) ? 1 : 0));
			}
		}
		
		/**
		 * Can be used to inspect the specified object. This method sends the object
		 * to the Alcon output panel where it is displayed in the Inspect tab.
		 * 
		 * @param object The object to be inspected.
		 * @param depth The depth with that to inspect the object. If this argument
		 * is -1 or omitted, the default from Alcon's config file will be used.
		 */
		public static function inspect(object:Object = null, depth:int = -1):void
		{
			send("onInspect", object, 1, depth);
		}
		
		/**
		 * Outputs a hexadecimal dump of the specified object.
		 * 
		 * @param object The object of which to output a hex dump.
		 */
		public static function hexDump(object:*):void
		{
			send("onHexDump", object, 0, 0);
		}
		
		/**
		 * Forces an immediate Garbage Collector mark/sweep. Use with caution!
		 * This method is not officially supported by the Flash Player!
		 */
		public static function forceGC():void
		{
			try
			{
				new LocalConnection().connect("forceGC");
				new LocalConnection().connect("forceGC");
			}
			catch (e:Error)
			{
			}
		}
		
		/**
		 * Sends a clear buffer signal to the output console. The Trace tab will be
		 * cleared after this signal is received.
		 */
		public static function clear():void
		{
			Debug.trace("[%CLR%]", 5);
		}
		
		/**
		 * Sends a delimiter signal to the output console.
		 */
		public static function delimiter():void
		{
			Debug.trace("[%DLT%]", 5);
		}
		
		/**
		 * Sends a pause signal to the output console.
		 */
		public static function pause():void
		{
			Debug.trace("[%PSE%]", 5);
		}
		
		/**
		 * Sends a time/date signal to the output console.
		 */
		public static function time():void
		{
			Debug.trace("[%TME%]", 5);
		}
	
		/**
		 * Sets the current logging filter level.
		 * 
		 * @param level A value for the filter level to be set. If no argument
		 *              is specified, 0 is used as default.
		 */
		public static function setFilterLevel(level:uint = 0):void
		{
			if (level >= 0 && level < 5) _filterLevel = level;
		}
		
		/**
		 * Returns the currently used logging filter level.
		 * 
		 * @return The current filter level.
		 */
		public static function getFilterLevel():int
		{
			return _filterLevel;
		}
		
		/**
		 * Disables the output coming from the Debug class. This can be used
		 * to quickly suppress all debug output without needing to remove
		 * function calls to the Debug class and it's imports.
		 */
		public static function disable():void
		{
			_isDisabled = true;
		}
		
		// FPS Polling Methods ////////////////////////////////////////////////////////
		
		/**
		 * When called starts measuring the current host applications frames per
		 * second and sends the FPS value and the current amount of memory used by
		 * the host applications Flash Player to the Alcon output console.
		 * 
		 * @param stage The Stage of the current host application.
		 */
		public static function fpsStart(stage:Stage):void
		{
			if (!_isDisabled && _fpsMeter == null)
			{
				_isPollingFPS = true;
				_stage = stage;
				_fpsMeter = new FPSMeter(stage);
				_fpsMeter.addEventListener(FPSMeter.FPS_UPDATE, onFPSUpdate);
				_fpsMeter.start();
			}
		}
		
		/**
		 * Stops the FPS polling.
		 */
		public static function fpsStop():void
		{
			if (_fpsMeter != null)
			{
				_isPollingFPS = false;
				_fpsMeter.stop();
				_fpsMeter.removeEventListener(FPSMeter.FPS_UPDATE, onFPSUpdate);
				_fpsMeter = null;
			}
		}
		
		// Timer Methods //////////////////////////////////////////////////////////////
		
		/**
		 * Starts the Stowatch to measure a time amount.
		 */
		public static function timerStart(title:String = ""):void
		{
			if (!_isDisabled)
			{
				if (_stopWatch == null) _stopWatch = new StopWatch();
				_stopWatch.start(title);
			}
		}
		
		/**
		 * Stops the Stowatch.
		 */
		public static function timerStop():void
		{
			if (_stopWatch != null) _stopWatch.stop();
		}
		
		/**
		 * Resets the Stopwatch.
		 */
		public static function timerReset():void
		{
			if (_stopWatch != null) _stopWatch.reset();
		}
		
		/**
		 * Sends the measured time in milliseconds to the output console.
		 */
		public static function timerMilliSeconds():void
		{
			if (_stopWatch != null) Debug.trace(_stopWatch.getTimeInMilliSeconds() + "ms");
		}
		
		/**
		 * Sends the measured time in seconds to the output console.
		 */
		public static function timerSeconds():void
		{
			if (_stopWatch != null) Debug.trace(_stopWatch.getTimeInSeconds() + "s");
		}
		
		/**
		 * Sends the measured time to the output console. This automatically
		 * formats the values to seconds and milliseconds.
		 */
		public static function timerToString():void
		{
			if (_stopWatch != null) Debug.trace(_stopWatch.toString());
		}
		
		/**
		 * Stops the Stopwatch and immediately Sends the measured time to the
		 * output console in the same manner like timerToString().
		 * 
		 * @param reset If true resets the Timer after the result has been output.
		 */
		public static function timerStopToString(reset:Boolean = false):void
		{
			if (_stopWatch != null)
			{
				_stopWatch.stop();
				Debug.trace(_stopWatch.toString());
				if (reset) _stopWatch.reset();
			}
		}
		
		// Private Methods ////////////////////////////////////////////////////////////
		
		/**
		 * Sends the specified data.
		 * 
		 * @private
		 */
		private static function send(method:String, data:*, level:int = 1, rec:uint = 0):void
		{
			// Only send if Debug is not disabled:
			if (!_isDisabled)
			{
				// Establish connection if not already done:
				if (!_isConnected)
				{
					_isConnected = true;
					_connection = new LocalConnection();
					_connection.addEventListener(StatusEvent.STATUS, onStatus);
				}
				
				// Get the size of the data:
				var size:uint = 0;
				if (typeof(data) == "string")
				{
					size = String(data).length;
				}
				else if (typeof(data) == "object")
				{
					var byteArray:ByteArray = new ByteArray();
					byteArray.writeObject(data);
					size = byteArray.length;
					byteArray = null;
				}
				
				// If the data size exceeds 39Kb, use a LSO instead:
				if (size > 39000)
				{
					storeDataLSO(method, data);
					method = "onLargeData";
					data = null;
				}
				
				_connection.send("_alcon_lc", method, data, level, rec, "");
			}
		}
		
		/**
		 * Stores data larger than 40Kb to a Local Shared Object.
		 * 
		 * @private
		 */
		private static function storeDataLSO(method:String, data:*):void
		{
			var sharedObject:SharedObject = SharedObject.getLocal("alcon", "/");
			sharedObject.data.alconMethod = method;
			sharedObject.data.alconData = data;
			try
			{
				var flushResult:String = sharedObject.flush();
				if (flushResult == SharedObjectFlushStatus.FLUSHED)
				{
					return;
				}
			}
			catch (e:Error)
			{
				Security.showSettings(SecurityPanel.LOCAL_STORAGE);
			}
		}
		
		/**
		 * Called on every fpsUpdate event.
		 * 
		 * @private
		 */
		private static function onFPSUpdate(event:Event):void
		{
			send("onFPS", (_fpsMeter.getFPS() + "/" + _stage.frameRate + "|" + System.totalMemory));
		}
		
		/**
		 * onStatus method
		 * 
		 * @private
		 */
		private static function onStatus(event:StatusEvent):void
		{
		}
	}
}
