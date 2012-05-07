<?php 
require_once(dirname(__FILE__).'/../classes/flatterizor.class.php');
class MyCustomClass1 {

}
$obj = new MyCustomClass1();
$obj->a = "a field";
$obj->b = array('an element');
$obj->empty_array = array();
$obj->el_array = array(1, 'very'=>array('nested'=>'array'));
$obj->empty_class = new stdClass();

$flatterizator = new ObjectFlatterizor(true);
$result = $flatterizator->flatterize($obj);

var_dump($result); // Will show you the Serialized object. Ready to be stored in a key value table

$rebuiltObject = ObjectFlatterizor::rebuild($result);

var_dump(serialize($rebuiltObject) == serialize($obj) ); // Will print true :)