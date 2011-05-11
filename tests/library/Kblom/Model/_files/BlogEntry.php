<?php
require_once 'Kblom/Model/Entity.php';
require_once 'Kblom/Model/Mapper.php';

class Model_BlogEntry extends Kblom_Model_Entity
{
	protected $_data = array(
		'id'      => null,
		'title'   => '',
		'content' => '',
		'author'  => null
	);

	public function getTitle()
	{
		return 'foo';
	}

	public function setAuthor(Kblom_Model_Entity $author)
	{
		$this->_data['author'] = $author;
	}
}
