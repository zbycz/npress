<?php
/**
 * nPress - opensource cms
 *
 * @copyright  (c) 2012 Pavel Zbytovský (pavel@zby.cz)
 * @link       http://npress.zby.cz/
 * @package    nPress
 */


/** Service for triggering events and Plugins management
 */
class Plugins extends Object {
	private $plugins = array();
	
	// array(eventName => array(pluginNames...), ...)
	private $events;
	
	function __construct($plugins){
		foreach((array) $plugins as $key=>$val)
			$this->plugins[] = is_int($key) ? $val : $key;

		$this->registerEvents();
	}
	
	function registerEvents(){
		foreach($this->plugins as $class){
			$eventnames = $class::$events; // onPageUpdate, onPageEditForm, ...
			
			foreach($eventnames as $event){
				if(!isset($this->events[$event])) 
					$this->events[$event] = array();
					
				$this->events[$event][] = $class;
			}
		}
	}
	
	function getPlugins(){
		return $this->plugins;
	}
	
	function getEventTriggers($event){
		if(!isset($this->events[$event]))
			return array(); 
			
		return $this->events[$event];
	}
}

