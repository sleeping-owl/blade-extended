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
		$this->blade = BladeExtended::instance();
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

		$this->blade->setContent('<div class="my-class" bd-class="$myClass">div</div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div class="{{ \SleepingOwl\BladeExtended\Helper::renderClass($myClass) }}my-class">div</div>', $content);
	}

	/** @test */
	public function it_selects_current_tag_class_attribute()
	{
		$this->blade->setContent('<div bd-class="$myClass"><b class="my-class">b</b></div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("class", $myClass) }}><b class="my-class">b</b></div>', $content);
	}

	/** @test */
	public function it_supports_attributes()
	{
		$this->blade->setContent('<div bd-attr-id="\'my-id\'"></div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("id",\'my-id\') }}></div>', $content);
	}

	/** @test */
	public function it_supports_attributes_with_dashes()
	{
		$this->blade->setContent('<div bd-attr-data-test="$test"></div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("data-test",$test) }}></div>', $content);
	}

	/** @test */
	public function it_throws_exception_when_attribute_already_exists()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$this->blade->setContent('<div bd-attr-id="$test" id="test"></div>');
		$content = $this->blade->parse();
	}

	/** @test */
	public function it_throws_exception_when_closing_tag_not_found()
	{
		$this->setExpectedException('\InvalidArgumentException');
		$this->blade->setContent('<div bd-attr-id="$test"><div></div>');
		$content = $this->blade->parse();
	}

	/** @test */
	public function it_supports_extensions()
	{
		BladeExtended::extend('bd-test', function (BladeExtended $bladeExtended, &$finded)
		{
			$bladeExtended->wrapOuterContent($finded, '@if(myCustomTest())', '@endif');
		});
		$this->blade->setContent('<div bd-test></div>');
		$content = $this->blade->parse();
		$this->assertEquals('@if(myCustomTest())<div></div>@endif', $content);
	}

	/** @test */
	public function it_supports_yield()
	{
		$this->blade->setContent('<div bd-yield="$template"></div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div>@yield($template)</div>', $content);
	}

	/** @test */
	public function it_supports_include()
	{
		$this->blade->setContent('<div bd-include="$template"></div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div>@include($template)</div>', $content);
	}

	/** @test */
	public function it_supports_unwrap()
	{
		$this->blade->setContent('<div><any bd-unwrap></any></div>');
		$content = $this->blade->parse();
		$this->assertEquals('<div></div>', $content);
	}

	/** @test */
	public function it_supports_section()
	{
		$this->blade->setContent('<div bd-section="\'content\'"></div>');
		$content = $this->blade->parse();
		$this->assertEquals('@section(\'content\')<div></div>@stop', $content);
	}

}
 