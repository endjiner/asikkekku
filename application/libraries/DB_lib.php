<?php
if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

class DB_lib {
	function __construct()	{
		$this->ci = &get_instance();
	}

}
?>