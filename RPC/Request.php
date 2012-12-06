<?php

/**
 * @see http://code.google.com/p/multirpc
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 */ 
class RPC_Request {

	protected $calls = array();
	protected $binds = array();

	public function bind($name, RPC_RequestCall $call) {
		$this->binds[$name] = $call;
	}

	public function clearBinds() {
		$this->binds = array();
	}

	public function addCall(RPC_RequestCall $call) {
		$this->calls[] = $call;
	}

	public function convertToString() {
		return serialize($this);
	}

	public static function initFromString($string) {
		$request = @unserialize($string);
		if(!$request) {
			throw new RPC_RequestFailed('Wrong init string');
		}
		return $request;
	}

	public function run() {
		$response = array();
		try {
			foreach($this->calls as $call) {
				$call->__init();
			}
			foreach($this->binds as $name => $call) {
				$response[$name] = $call->value;
			}
		}
		catch(Exception $exception) {
			throw new RPC_RequestCallFailedOnServer($call, $exception);
		}
		return $response;
	}

	public function __set($var, $value) {
		$this->response[$var] = $value;
	}

	public function __call($funcName, $args = array()) {
		return new RPC_RequestFunction($this, $funcName, $args);
	}
}

abstract class RPC_RequestCall {

	public $value;
	protected $name;

	/**
	 * @var RPC_Request
	 */
	public $request;

	public function __construct(RPC_Request $request) {
		$this->request = $request;
		$this->request->addCall($this);
	}

	public function __get($var) {
		return new RPC_RequestGetObjectProperty($this, $var);
	}

	public function __set($var, $value) {
		return new RPC_RequestSetObjectProperty($this, $var, $value);
	}

	public function __call($method, $args = array()) {
		return new RPC_RequestObjectMethod($this, $method, $args);
	}

	public function __staticMethod($method, $args = array()) {
		return new RPC_RequestObjectStaticMethod($this, $method, $args);
	}

	public function __getNow() {
	}

	public function getName() {
		return $this->name ? $this->name : get_class($this);
	}

	public function __sleep() {
		$vars = array_keys(get_object_vars($this));
		unset($vars[array_search('request', $vars)]);
		return $vars;
	}

	protected static function __varsToValues($vars) {
		if(is_array($vars)) {
			foreach($vars as $i => $var) {
				if($var instanceof RPC_RequestCall) {
					$vars[$i] =& $var->value;
				}
			}
		}
		return $vars;
	}

	public function __init() {
		if(!$this->__isAllowedToInit()) {
			throw new RPC_RequestCallNotAllowed($this);
		}
		else {
			$this->value = $this->__initValue();
		}
	}

	protected function __isAllowedToInit() {
		return true;
	}

	abstract protected function __initValue();
}

class RPC_RequestVariable extends RPC_RequestCall {

	public $value;

	public function __construct(RPC_Request $request, $value = null) {
		parent::__construct($request);
		$this->value = $value;
	}

	protected function __initValue() {
		return $this->value;
	}
}

class RPC_RequestSetObjectProperty extends RPC_RequestCall {

	protected $object;
	protected $property;

	public function __construct(RPC_RequestCall $object, $property, $value) {
		parent::__construct($object->request);
		$this->object = $object;
		$this->property = $property;
		$this->value = $value;
	}

	public function getName() {
		return get_class($this->object) . '->' . $this->property;
	}

	protected function __initValue() {
		if($this->object->value) {
			$this->object->value->{$this->property} = $this->value;
			return $this->value;
		}
	}
}

class RPC_RequestGetObjectProperty extends RPC_RequestCall {

	protected $object;
	protected $property;

	public function __construct(RPC_RequestCall $object, $property) {
		parent::__construct($object->request);
		$this->object = $object;
		$this->property = $property;
	}

	public function getName() {
		return get_class($this->object) . '->' . $this->property;
	}

	protected function __initValue() {
		if($this->object->value) {
			return $this->object->value->{$this->property};
		}
	}
}

class RPC_RequestFunction extends RPC_RequestCall {

	public static $allowedFuncs;

	protected $name;
	protected $args;

	public function __construct(RPC_Request $request, $name, $args = array()) {
		parent::__construct($request);
		$this->name = $name;
		$this->args = $args;
	}

	public function getName() {
		return $this->name . '(...)';
	}

	protected function __isAllowedToInit() {
		return !is_array(self::$allowedFuncs) || in_array($this->name, self::$allowedFuncs);
	}

	protected function __initValue() {
		return call_user_func_array($this->name, self::__varsToValues($this->args));
	}
}

class RPC_RequestObjectMethod extends RPC_RequestCall {

	protected $object;
	protected $method;
	protected $args;

	public function __construct(RPC_RequestCall $object, $method, $args = array()) {
		parent::__construct($object->request);
		$this->object = $object;
		$this->method = $method;
		$this->args = $args;
	}

	public function getName() {
		return get_class($this->object) . '->' . $this->method . '(...)';
	}

	protected function __initValue() {
		if($this->object->value) {
			return call_user_func_array(array($this->object->value, $this->method), self::__varsToValues($this->args));
		}
	}
}

class RPC_RequestClassStaticMethod extends RPC_RequestCall {

	public static $allowedClasses;

	protected $class;
	protected $method;
	protected $args;

	public function __construct(RPC_Request $request, $class, $method, $args = array()) {
		parent::__construct($request);
		$this->class = $class;
		$this->method = $method;
		$this->args = $args;
	}

	protected function __isAllowedToInit() {
		return !is_array(self::$allowedClasses) || in_array($this->class, self::$allowedClasses);
	}

	public function getName() {
		return $this->class . '::' . $this->method . '(...)';
	}

	protected function __initValue() {
		return call_user_func_array(array($this->class, $this->method), self::__varsToValues($this->args));
	}
}

class RPC_RequestObjectStaticMethod extends RPC_RequestCall {

	protected $object;
	protected $method;
	protected $args;

	public function __construct(RPC_RequestCall $object, $method, $args = array()) {
		parent::__construct($object->request);
		$this->object = $object;
		$this->method = $method;
		$this->args = $args;
	}

	public function getName() {
		return get_class($this->object) . '::' . $this->method . '(...)';
	}

	protected function __initValue() {
		if($this->object->value) {
			return call_user_func_array(array($this->object->value, $this->method), self::__varsToValues($this->args));
		}
	}
}

class RPC_RequestClassStaticProperty extends RPC_RequestCall {

	protected $class;
	protected $property;

	public function __construct(RPC_Request $request, $class, $property) {
		parent::__construct($request);
		$this->class = $class;
		$this->property = $property;
	}

	public function getName() {
		return $this->call . '::$' . $this->property;
	}

	protected function __initValue() {
		$class = $this->class;
		$property = $this->property;
		return $class::$$property;
	}
}

class RPC_RequestInitObject extends RPC_RequestCall {

	public static $allowedClasses;

	protected $class;
	protected $args;

	public function __construct(RPC_Request $request, $class, $args = array()) {
		parent::__construct($request);
		$this->class = $class;
		$this->args = $args;
	}

	public function getName() {
		return ' new ' . get_class($this->object) . '(...)';
	}

	protected function __isAllowedToInit() {
		return !is_array(self::$allowedClasses) || in_array($this->class, self::$allowedClasses);
	}

	protected function __initValue() {
		$reflectionClass = new ReflectionClass($this->class);
		return $this->args ? $reflectionClass->newInstanceArgs(self::__varsToValues($this->args)) : $reflectionClass->newInstance();
	}
}

class RPC_Exception extends Exception {

}

/**
 * Required because you can't catch-throw server exception, because some classes can't be not defined on client
 */
class RPC_ServerExceptionData {

	public $callName;
	public $class;
	public $message;
	public $source;
	public $trace;
}

class RPC_ServerException extends RPC_Exception implements Serializable {

	protected $trace;

	public function serialize() {
		return serialize(array(
				$this->validator,
				$this->arguments,
				$this->getCode(),
				$this->getMessage(),
				$this->getTraceAsString(),
				$this->getFile(),
				$this->getLine(),
			));
	}

	public function unserialize($serialized) {
		list(
			$this->validator,
			$this->arguments,
			$this->code,
			$this->message,
			$this->trace,
			$this->file,
			$this->line,
			) = unserialize($serialized);
	}

	public function getOriginalTrace() {
		return $this->trace;
	}
}

class RPC_RequestCallFailedOnServer extends RPC_ServerException {

	/**
	 * @var RPC_RequestCall
	 */
	protected $call;

	/**
	 * @var Exception
	 */
	protected $exception;

	public function __construct(RPC_RequestCall $call, Exception $exception) {
		parent::__construct('Request call ' . $call->getName() . ' failed with exception ' . get_class($exception) . ': ' . $exception->message . "\n\nSource: " . $exception->getFile() . ':' . $exception->getLine() . "\n\nTrace:\n" . $exception->getTraceAsString());

		$this->call = $call;
		$this->exception = $exception;
	}

	public function getCall() {
		return $this->call;
	}

	public function getException() {
		return $this->exception;
	}
}

class RPC_RequestCallNotAllowed extends RPC_Exception {

	protected $call;

	public function __construct(RPC_RequestCall $call) {
		$this->call = $call;
		parent::__construct('Requested call is not allowed: ' . print_r($call, true));
	}
}

