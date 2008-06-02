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
	import flash.utils.getTimer;
	
	/**
	 * Stopwatch stops the time.
	 * 
	 * Instantiate this class as follows:
	 *   import com.hexagonstar.util.StopWatch;
	 *   var stopWatch:StopWatch = new StopWatch();
	 * 
	 * This will create a still standing stopwatch. You can start
	 * and stop the stopwatch to record time as you please.
	 *   stopWatch.start();
	 *   // Do something
	 *   stopWatch.stop();
	 * 
	 * The recored time is available in milliseconds and seconds.
	 *   trace(stopWatch.getTimeInMilliSeconds() + " ms");
	 *   trace(stopWatch.getTimeInSeconds() + " s");
	 * 
	 */
	public class StopWatch
	{
		private var _started:Boolean = false;
		private var _startTimeKeys:Array;
		private var _stopTimeKeys:Array;
		private var _title:String;
		
		/** 
		 * Constructs a new StopWatch instance.
		 */
		public function StopWatch()
		{
			reset();
		}
		
		/**
		 * Resets the stopwatch total running time.
		 */
		public function reset():void
		{
			_startTimeKeys = new Array();
			_stopTimeKeys = new Array();
			_started = false;
		}
		
		/**
		 * Starts the time recording process.
		 */
		public function start(title:String = ""):void
		{
			if (!hasStarted())
			{
				_title = title;
				_started = true;
				_startTimeKeys.push(getTimer());
			}
		}
		
		/**
		 * Stops the time recording process if the process has been started before.
		 */
		public function stop():void
		{
			if (hasStarted())
			{
				var stopTime:uint = getTimer();
				_stopTimeKeys[_startTimeKeys.length - 1] = stopTime;
				_started = false;
			}
		}
		
		/**
		 * Returns whether this stopwatch has been started.
		 * 
		 * @return           true if this stopwatch has been started else false.
		 */
		public function hasStarted():Boolean
		{
			return _started;
		}
		
		/**
		 * Calculates and returns the elapsed time in milliseconds.
		 * This stopwatch will not be stopped by calling this method. If this stopwatch
		 * is still running it takes the current time as stoptime for the result.
		 * 
		 * @return           the elapsed time in milliseconds.
		 */
		public function getTimeInMilliSeconds():int
		{
			if (hasStarted())
			{
				_stopTimeKeys[_startTimeKeys.length - 1] = getTimer();
			}
			var result:int = 0;
			for (var i:int = 0; i < _startTimeKeys.length; i++)
			{
				result += (_stopTimeKeys[i] - _startTimeKeys[i]);
			}
			return result;		
		}
		
		/**
		 * Calculates and returns the elapsed time in seconds.
		 * This stopwatch will not be stopped by calling this method. If this stopwatch
		 * is still running it takes the current time as stoptime for the result.
		 * 
		 * @return           the elapsed time in seconds.
		 */
		public function getTimeInSeconds():Number
		{
			return getTimeInMilliSeconds() / 1000;
		}
		
		/**
		 * Generates a string representation of this stopwatch that includes all start and
		 * stop times in milliseconds.
		 * 
		 * @return           the string representation of this stopwatch.
		 */
		public function toString():String
		{
			var result:String = "\n ********** [STOPWATCH] **********";
			if (_title != "") result += "\n  " + _title;
			for(var i:int = 0; i < _startTimeKeys.length; i++)
			{
				result += "\n  started [" + _startTimeKeys[i]
					+ "ms] stopped [" + _stopTimeKeys[i] + "ms]";
			}
			if (i == 0) result += "\n  never started.";
			else result += "\n  total runnning time: " + getTimeInMilliSeconds() + "ms";
			result += "\n *********************************";
			return result;
		}
	}
}
