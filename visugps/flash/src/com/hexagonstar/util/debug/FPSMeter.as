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
	import flash.events.EventDispatcher;
	import flash.events.TimerEvent;
	import flash.utils.Timer;
	
	/**
	 * Simple Stage framrate measuring class.
	 */
	public class FPSMeter extends EventDispatcher
	{
		// Properties /////////////////////////////////////////////////////////////////
		
		public static const FPS_UPDATE:String = "fpsUpdate";
		
		private var _stage:Stage;
		private var _timer:Timer;
		private var _fps:int;
		private var _isRunning:Boolean;
		
		// Constructor ////////////////////////////////////////////////////////////////
		
		/**
		 * Constructor
		 */
		public function FPSMeter(stage:Stage)
		{
			_stage = stage;
			_fps = 0;
			_isRunning = false;
		}
		
		// Public Methods /////////////////////////////////////////////////////////////
		
		/**
		 * Starts FPS polling.
		 */
		public function start(pollInterval:uint = 1000):void
		{
			if (!_isRunning)
			{
				_isRunning = true;
				_timer = new Timer(pollInterval, 0);
				_timer.addEventListener(TimerEvent.TIMER, onTimer);
				_stage.addEventListener(Event.ENTER_FRAME, onEnterFrame);
				_timer.start();
			}
		}
		
		/**
		 * Stops FPS polling.
		 */
		public function stop():void
		{
			if (_isRunning)
			{
				_isRunning = false;
				_timer.stop();
				_timer.removeEventListener(TimerEvent.TIMER, onTimer);
				_stage.removeEventListener(Event.ENTER_FRAME, onEnterFrame);
				_timer = null;
			}
		}
		
		/**
		 * Returns the current FPS.
		 * 
		 * @return The currently polled frames per second.
		 */
		public function getFPS():int
		{
			return _fps;
		}
		
		// Private Methods ////////////////////////////////////////////////////////////
		
		/**
		 * Called on every Timer event.
		 * @private
		 */
		private function onTimer(event:TimerEvent):void
		{
			dispatchEvent(new Event(FPSMeter.FPS_UPDATE));
			_fps = 0;
		}
		
		/**
		 * Called on every EnterFrame event.
		 * @private
		 */
		private function onEnterFrame(event:Event):void
		{
			_fps++;
		}
	}
}
