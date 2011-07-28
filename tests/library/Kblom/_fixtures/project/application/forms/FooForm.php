<?php
class FooForm extends Zend_Form
{
	public function init()
	{
		$e1 = new Zend_Form_Element_Text('Element1', array('label' => 'Form element 1', 'description' => 'Description of form element 1'));

		Zend_Form_Element_Text('moo');
		Zend_Form_Element_Text('Element1', array(
			'label' => 'diipa',
			'description' => 'Description of form element 1'
		));

		$this->addElements($e1);
	}
}

