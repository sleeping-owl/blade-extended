<?php

use SleepingOwl\BladeExtended\BladeExtended;

class BladeExtendedTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var BladeExtended
	 */
	protected $blade;

	protected function setUp()
	{
		parent::setUp();
		$this->blade = new BladeExtended;
	}

	/** @test */
	public function it_supports_foreach()
	{
		$this->blade->setContent('<li bd-foreach="$items as $item">content</li>');
		$content = $this->blade->parse();
		$this->assertStringStartsWith('@foreach($items as $item)<li>content</li>@endforeach', $content);
	}

	/** @test */
	public function it_supports_foreach_with_nested_tags()
	{
		$this->blade->setContent('<div bd-foreach="$items as $item"><div>first</div><div><div>second</div></div>content</div>');
		$content = $this->blade->parse();
		$this->assertStringStartsWith('@foreach($items as $item)<div><div>first</div><div><div>second</div></div>content</div>@endforeach', $content);
	}

	/** @test */
	public function it_supports_foreach_with_short_tags()
	{
		$this->blade->setContent('<br bd-foreach="$items as $item"/>');
		$content = $this->blade->parse();
		$this->assertStringStartsWith('@foreach($items as $item)<br/>@endforeach', $content);
	}

	/** @test */
	public function it_supports_inner_foreach()
	{
		$this->blade->setContent('<ul bd-inner-foreach="$items as $item"><li>content</li></ul>');
		$content = $this->blade->parse();
		$this->assertEquals('<ul>@foreach($items as $item)<li>content</li>@endforeach </ul>', $content);
	}

	/** @test */
	public function it_supports_if()
	{
		$this->blade->setContent('<div bd-if="$condition">div</div>');
		$content = $this->blade->parse();
		$this->assertStringStartsWith('@if($condition)<div>div</div>@endif', $content);
	}

	/** @test */
	public function it_supports_static_classes()
	{
		$this->blade->setContent('<div bd-class="\'my-class\'">div</div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("class", \'my-class\') }}>div</div>', $content);
	}

	/** @test */
	public function it_supports_dynamic_classes()
	{
		$this->blade->setContent('<div bd-class="$myClass">div</div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("class", $myClass) }}>div</div>', $content);
	}

	/** @test */
	public function it_supports_multiple_classes()
	{
		$this->blade->setContent('<div bd-class="$myClass, $myClass2, \'my-class\'">div</div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("class", $myClass, $myClass2, \'my-class\') }}>div</div>', $content);
	}

	/** @test */
	public function it_supports_short_conditions_in_classes()
	{
		$this->blade->setContent('<div bd-class="$myClass ? \'red\', $myClass2 ? \'blue\', \'my-class\'">div</div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("class", $myClass ? \'red\' : NULL, $myClass2 ? \'blue\' : NULL, \'my-class\') }}>div</div>', $content);
	}

	/** @test */
	public function it_supports_adding_classes_to_existing_class_attribute()
	{
		$this->blade->setContent('<div bd-class="$myClass" class="my-class">div</div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div class="{{ \SleepingOwl\BladeExtended\Helper::renderClass($myClass) }}my-class">div</div>', $content);
	}

	/** @test */
	public function it_select_corrent_class_attribute()
	{
		$this->blade->setContent('<div bd-class="$myClass"><b class="my-class">b</b></div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("class", $myClass) }}><b class="my-class">b</b></div>', $content);
	}

}
 