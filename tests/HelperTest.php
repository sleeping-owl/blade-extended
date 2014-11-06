<?php

use SleepingOwl\BladeExtended\Helper;

class HelperTest extends PHPUnit_Framework_TestCase
{

	/** @test */
	public function it_renders_class()
	{
		$result = Helper::renderClass(false, null, 'class');
		$this->assertEquals('class ', $result);

		$result = Helper::renderClass(false, null, '');
		$this->assertEquals('', $result);
	}

	/** @test */
	public function it_renders_attribute()
	{
		$result = Helper::renderAttribute('id', false, null, 'my-id');
		$this->assertEquals(' id="my-id"', $result);

		$result = Helper::renderAttribute('id', false, null, '');
		$this->assertEquals('', $result);
	}

}
 