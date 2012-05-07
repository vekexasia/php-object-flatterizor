<?php
/**
 * ObjectFlatterizor class. 
 * Make your nested object ready to be stored in a flat table. ( And bring the obj back to live from them )
 * 
 * 
 * @author Andrea Baccega <vekexasia@gmai.com>
 * @contributor Emanuele 'Tex' Tessore <setola@gmail.com>
 * @version 1.0 07-05-2012
 * 
 */
class ObjectFlatterizor {
	private static $emptyToken	= "\0_empty";
	private static $arrayRegexp = '@^\[.*]$@';

	public static $reservedKeywords = array("\0_empty");

	private $allowEmptyObjs		= false;
	/**
	 * @param int $maxDepthLevel Defines the maximum level of object flatterization
	 */
	public function __construct($storeEmptyObjects=false) {

		$this->allowEmptyObjs	= $storeEmptyObjects;
	}
	/**
	 * Function that flatterizes the object recursively. It uses the internal function _flatterize
	 * @param object|array $obj the object|array that needs to be flatterized
	 * @return object with 2 keys<br/>
	 * <ul>
	 *	<li><b>objectDefinitions</b>: which defines the object original class names</li>
	 *	<li><b>values</b>: which is an array of arrays. Each array is Associative and will be in the form:
	 *	<pre>
	 *		array (
	 *			'path'	=>	objXpath,
	 *			'val'	=>	value
	 *		)
	 *	</pre>
	 * </li>
	 * </ul>
	 *
	 */
	public function flatterize($toFlatterize) {
		if (is_null($toFlatterize) || ( ! is_object($toFlatterize) && ! is_array($toFlatterize))) {
			throw new InvalidArgumentException("The element should be object or array");
		}
		return self::_flatterize($toFlatterize);
	}

	private function _flatterize($obj,$curPath ='') {
		$toRet = new stdclass();
		$toRet->objectDefinitions = array();
		$toRet->values = array();

		if (is_object($obj)) {
			$toRet->objectDefinitions['/'.$curPath] = get_class($obj);

			if (count(get_object_vars($obj))==0 && $this->allowEmptyObjs)  {
				$toRet->values[] = array('path'=>$curPath.'/'.self::$emptyToken , 'val'=>'');
			}


			foreach ($obj as $k=>$v) {
				$res = self::_flatterize($v, $curPath.'/'.urlencode($k));
				$toRet->objectDefinitions = array_merge($toRet->objectDefinitions, $res->objectDefinitions);
				$toRet->values = array_merge($toRet->values, $res->values);
			}

		} else if (is_array($obj)) {

			if (empty($obj) && $this->allowEmptyObjs) {
				$toRet->values[] = array('path'=>$curPath.'/'.self::$emptyToken , 'val'=>'');
				
			}

			foreach ($obj as $k=>$v) {
				$res = self::_flatterize($v, $curPath.'/['.urlencode($k).']');
				$toRet->objectDefinitions = array_merge($toRet->objectDefinitions, $res->objectDefinitions);
				$toRet->values = array_merge($toRet->values, $res->values);
			}

		} else {
			$toRet->values[] = array('path'=>$curPath, 'val'=>$obj);
		}
		return $toRet;
	}

	private function fixToken($token) {
		return urldecode(str_replace(array('[',']'), '', $token));
	}

	/**
	 * Using the output of flatterize we'll be able to rebuild the original object.
	 *
	 * @param object|array $arr :<br/>
	 * <ul>
	 * 	<li>If <b>object</b>: the object must contain two keys:
	 *	<ul>
	 *		<li><b>objectDefinitions</b>: which defines the object original class names</li>
	 *		<li><b>values</b>: which is an array of arrays. Each array is Associative and must be in the form:
	 *  <pre>
	 *		array (
	 *			'path'	=>	objXpath,
	 *			'val'	=>	value
	 *		)
	 *	</pre></li>
	 *	</ul></li>
	 *	<li>If <b>array</b>: the parameter will be threated as the "values" array above mentioned.</li>
	 *</ul>
	 *
	 */
	public static function rebuild($thing) {
		if (is_null($thing) || ( ! is_object($thing) && ! is_array($thing))) {
			throw new InvalidArgumentException("The element should be object or array");
		}

		$tmp = new stdClass();
		if (is_object($thing) && isset($thing->objectDefinitions) && is_array($thing->objectDefinitions)) {
			$tmp->objectDefinitions		= $thing->objectDefinitions;
			$tmp->values			 	= $thing->values;
		} else {
			$tmp->objectDefinitions		= array();
			$tmp->values			 	= $thing; // Probably developer just passed over the values array.
		}
		return self::_rebuild($tmp);
	}

	private static function _rebuild($obj) {
		if (isset($obj->objectDefinitions['/'])) {
			if (class_exists($obj->objectDefinitions['/'])) {
				$toRet = new $obj->objectDefinitions['/']();
			} else {
				$toRet = new stdClass();
			}
		} else {
			$toRet = array();
		}

		for ($j=0;$j<count($obj->values); $j++) {
			extract($obj->values[$j]); // should extract $path & $val
				
			$pTokens	= explode('/',ltrim($path,'/'));
				
			$cursor		= & $toRet; // used to traverse the "object tree"
			$cursorToken = ''; // used to rebuild data types.
				
			for ($i=0; $i<count($pTokens)-1; $i++) {
				$par = self::fixToken($pTokens[$i]);
				$cursorToken .= '/'.$pTokens[$i];
				if (is_array($cursor)) {
					if ( ! isset($cursor[$par])) {
						if ( preg_match(self::$arrayRegexp, $pTokens[$i+1]) || ( $pTokens[$i+1] == self::$emptyToken && ! isset($obj->objectDefinitions['/'.$cursorToken]) )) {
							$cursor[$par] = array();
						} else {
							$className			= $obj->objectDefinitions['/'.$cursorToken];
							$className			= class_exists($className)?$className:'stdClass';
							$cursor[$par]		= new $className();
						}
					}
					$cursor = & $cursor[$par];
				} else {
					if ( ! isset($cursor->$par)) {
						if ( preg_match(self::$arrayRegexp, $pTokens[$i+1]) || ( $pTokens[$i+1] == self::$emptyToken && ! isset($obj->objectDefinitions['/'.$cursorToken]) )) {
							$cursor->{$par} = array();
						} else {
							$className			= $obj->objectDefinitions['/'.$cursorToken];
							$className			= class_exists($className)?$className:'stdClass';
							$cursor->{$par}		= new $className();
								
						}
					}
					$cursor = & $cursor->$par;
				}
			}
				
			$lastToken = array_pop($pTokens);
			if ( ! in_array($lastToken, self::$reservedKeywords)) {
				if (is_array($cursor)) {
					$cursor[self::fixToken($lastToken)]		= $val;
				} else {
					$cursor->{self::fixToken($lastToken)}	= $val;
				}
			}

		}

		return $toRet;
	}
}
