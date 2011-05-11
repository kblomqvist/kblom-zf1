<?php
require_once 'Kblom/Model/Entity.php';

class Model_Author extends Kblom_Model_Entity
{
	protected $_data = array(
		'id'        => null,
		'firstName' => '',
		'lastName'  => ''
	);
}
