Php Object Flatterizor
======================

Flat your "nested" objects and rebuild it later.

Why?
----

Storing the data in complex and nested objects you probably already faced the need to save these objects to a common _flat_ datastore like a MySQL table.

Why not using serialize() or json_encode() ? 
--------------------------------------------

Sometimes it gets handy to perform a search on a specific field value of your object. If you store the object using serialize or json_encode performing an SQL query that perform the mentioned task could cause headaches.

* * *

Example
=======

We have the following object that we want to translate in a key,value table.
	<?php 
	
	class MyCustomClass1 {
	
	}
	
	$obj = new MyCustomClass1();
	$obj->a = "a field";
	$obj->b = array('an element', array('nested'=> (object) array('yes' => array( 'i'=> 'am' ) ) ) );

As you would probably notice, the object has an intentionally heavy nesting level. Infact:
	
	<?php
	echo $obj->b[1]['nested']->yes['i']; // prints "am"

The library will provide you a list of "__path__" + "__values__" . For the path in the example the library will generate something like this:
	
	[1]=> array(2) {
      ["path"]=>
      	string(23) "/b/[1]/[nested]/yes/[i]"
      ["val"]=>
      	string(2) "am"
    }

In this way you could use the path as "key" and you could fetch all the objects that at that key have a specific val.

I suggest to take a look to the _demo/_ folder which contains demo(s) on __how to use__ the lbirary.

Authors
=======
*  Andrea Baccega <me@andreabaccega.com> - _Author/Ideator of the library_
*  Emanuele 'Tex' Tessore <setola@gmail.com> - _Contributor of the library_
