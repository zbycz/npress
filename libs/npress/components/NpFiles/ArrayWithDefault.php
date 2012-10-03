<?php

/** Behaves like normal array, but non-existent keys return supplied default value
 */
class ArrayWithDefault implements ArrayAccess {
	private $data;
	private $defaultValue;
	function __construct($defaultValue, $initArray=array()){$this->defaultValue=$defaultValue; $this->data=$initArray;}
	function offsetGet($k){return isset($this->data[$k]) ? $this->data[$k] : $this->defaultValue;}
	function offsetExists($k){return true;}
	function offsetSet($k, $v){$this->data[$k] = $v;}
	function offsetUnset($k){unset($this->data[$k]);}
}
