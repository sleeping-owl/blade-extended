<?php namespace SleepingOwl\BladeExtended;

use \Blade;
use Closure;

class BladeExtended
{

	/**
	 * @var BladeExtended
	 */
	protected static $instance;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var array
	 */
	protected $macros = [
		'bd-foreach'                        => 'Foreach',
		'bd-inner-foreach'                  => 'InnerForeach',
		'bd-if'                             => 'If',
		'bd-class'                          => 'Class',
		'bd-attr-(?<attribute>[a-zA-Z-_]+)' => 'Attr',
	];

	/**
	 * @var array
	 */
	protected $extensions = [];

	/**
	 * @return BladeExtended
	 */
	public static function instance()
	{
		if (is_null(static::$instance))
		{
			static::$instance = new static;
		}
		return static::$instance;
	}

	/**
	 * Register Blade extenstion
	 */
	public static function register()
	{
		Blade::extend(function ($content)
		{
			$me = static::instance();
			$me->setContent($content);
			return $me->parse();
		});
	}

	/**
	 * @param $attribute
	 * @param callable $callback
	 */
	public static function extend($attribute, Closure $callback)
	{
		$me = static::instance();
		$me->registerExtension($attribute, $callback);
	}

	/**
	 * @param string $content
	 */
	public function setContent($content)
	{
		$this->content = $content;
	}

	/**
	 * Parse content
	 *
	 * @return string
	 */
	public function parse()
	{
		$macros = array_merge($this->macros, $this->extensions);
		foreach ($macros as $attribute => $method)
		{
			while ($finded = $this->find($attribute))
			{
				if (is_callable($method))
				{
					$method($this, $finded);
				} else
				{
					$this->{'parse' . $method}($finded);
				}
				$this->deleteAttribute($attribute, $finded['opening']['start'], $finded['opening']['end']);
			}
		}
		return $this->content;
	}

	/**
	 * @param $finded
	 * @return bool
	 */
	protected function parseIf(&$finded)
	{
		$this->wrapOuterContent($finded, '@if(:value)', '@endif ');
	}

	/**
	 * @param $finded
	 */
	protected function parseClass(&$finded)
	{
		$value = $this->parseShortSyntax($finded['value']);

		$tag = substr($this->content, $finded['opening']['start'], $finded['opening']['end'] - $finded['opening']['start']);
		if (preg_match('~\sclass="(?<class>.*?)"~', $tag, $matches, PREG_OFFSET_CAPTURE))
		{
			$class = '{{ \SleepingOwl\BladeExtended\Helper::renderClass(' . $value . ') }}';
			$this->insertContent($finded['opening']['start'] + $matches['class'][1], $class);
		} else
		{
			$class = '{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("class", ' . $value . ') }}';
			$this->replaceAttribute('bd-class', $class, $finded['opening']['start'], $finded['opening']['end']);
		}
	}

	/**
	 * @param $finded
	 */
	protected function parseAttr(&$finded)
	{
		$attribute = $finded['attribute'];
		$value = $this->parseShortSyntax($finded['value']);

		$tag = substr($this->content, $finded['opening']['start'], $finded['opening']['end'] - $finded['opening']['start']);
		if (preg_match('~\s' . $attribute . '="(.*?)"~', $tag, $matches, PREG_OFFSET_CAPTURE))
		{
			throw new \InvalidArgumentException("bd-attr-$attribute can't be used with existing attribute $attribute");
		}
		$attr = '{{ \SleepingOwl\BladeExtended\Helper::renderAttribute("' . $attribute . '",' . $value . ') }}';
		$this->replaceAttribute('bd-attr-' . $attribute, $attr, $finded['opening']['start'], $finded['opening']['end']);
	}

	/**
	 * @param $finded
	 */
	protected function parseForeach(&$finded)
	{
		$this->wrapOuterContent($finded, '@foreach(:value)', '@endforeach ');
	}

	/**
	 * @param $finded
	 */
	protected function parseInnerForeach(&$finded)
	{
		$this->wrapInnerContent($finded, '@foreach(:value)', '@endforeach ');
	}

	/**
	 * Find tag with $attribute
	 *
	 * @param $attribute
	 * @return array|bool
	 */
	protected function find($attribute)
	{
		if ( ! preg_match('~<(?<tagname>[a-zA-Z]+)[^<>]*?\s?' . $attribute . '="(?<value>.+?)".*?/?>~', $this->content, $matches, PREG_OFFSET_CAPTURE))
		{
			return false;
		}
		$result = [
			'opening' => [
				'start' => $matches[0][1],
				'end'   => $matches[0][1] + strlen($matches[0][0])
			]
		];
		foreach ($matches as $key => $data)
		{
			if (is_numeric($key)) continue;
			$result[$key] = $data[0];
		}
		$result['closing'] = $this->findTagClosingPosition($result['tagname'], $result['opening']['end']);
		return $result;
	}

	/**
	 * Find closing tag inner and outer position
	 *
	 * @param $tagname
	 * @param $offset
	 * @return array
	 */
	protected function findTagClosingPosition($tagname, $offset)
	{
		$start = $offset;
		if (substr($this->content, $offset - 2, 2) === '/>')
		{
			# short tag <br/>
			return [
				'start' => $offset,
				'end'   => $offset
			];
		}
		$opening = 1;
		$closing = 0;
		$innerEnd = $offset;
		while ($opening !== $closing)
		{
			if ( ! preg_match('~</' . $tagname . '>~', $this->content, $matches, PREG_OFFSET_CAPTURE, $offset))
			{
				throw new \InvalidArgumentException('Closing tag </' . $tagname . '> was not found.');
			}
			$offset = $matches[0][1] + strlen($matches[0][0]);
			$innerEnd = $matches[0][1];
			$innerContent = substr($this->content, $start, $offset - $start);
			$opening = 1 + preg_match_all('~<' . $tagname . '(?![^<>]*/>)~', $innerContent);
			$closing = preg_match_all("~</$tagname~", $innerContent);
		}
		return [
			'start' => $innerEnd,
			'end'   => $offset
		];
	}

	/**
	 * Insert $string to result at $position
	 *
	 * @param $position
	 * @param $string
	 */
	public function insertContent($position, $string)
	{
		$this->content = substr($this->content, 0, $position) . $string . substr($this->content, $position);
	}

	/**
	 * Remove part of string from content
	 *
	 * @param $from
	 * @param $to
	 */
	public function removeContent($from, $to)
	{
		$this->content = substr($this->content, 0, $from) . substr($this->content, $to);
	}

	/**
	 * Replace whole tag attribute with $replacement
	 *
	 * @param $attribute
	 * @param $replacement
	 * @param $start
	 * @param $end
	 */
	public function replaceAttribute($attribute, $replacement, $start, $end)
	{
		$tag = substr($this->content, $start, $end - $start);
		if (preg_match('~\s?' . $attribute . '=".+?"~', $tag, $matches, PREG_OFFSET_CAPTURE))
		{
			$attributeStart = $start + $matches[0][1];
			$attributeEnd = $attributeStart + strlen($matches[0][0]);
			$this->removeContent($attributeStart, $attributeEnd);
			$this->insertContent($attributeStart, $replacement);
		}
	}

	/**
	 * Delete tag attribute
	 *
	 * @param $attribute
	 * @param $start
	 * @param $end
	 */
	public function deleteAttribute($attribute, $start, $end)
	{
		$this->replaceAttribute($attribute, '', $start, $end);
	}

	/**
	 * Wrap tag with $before and $after
	 *
	 * @param $finded
	 * @param $before
	 * @param $after
	 */
	public function wrapOuterContent(&$finded, $before, $after)
	{
		$value = $finded['value'];
		$start = $finded['opening']['start'];
		$insertion = strtr($before, [':value' => $value]);
		$this->insertContent($start, $insertion);
		$this->moveOpeningPositionBy($finded, strlen($insertion));
		$closingPosition = $finded['closing']['end'] + strlen($insertion);
		$this->insertContent($closingPosition, $after);
	}

	/**
	 * Wrap tag inner content with $before and $after
	 *
	 * @param $finded
	 * @param $before
	 * @param $after
	 */
	public function wrapInnerContent($finded, $before, $after)
	{
		$value = $finded['value'];
		$end = $finded['opening']['end'];
		$insertion = strtr($before, [':value' => $value]);
		$this->insertContent($end, $insertion);
		$closingPosition = $finded['closing']['start'] + strlen($insertion);
		$this->insertContent($closingPosition, $after);
	}

	/**
	 * @param $value
	 * @return mixed
	 */
	public function parseShortSyntax($value)
	{
		return preg_replace('~(.+?\?[^:]+?)($|,)~', '\1 : NULL\2', $value);
	}

	/**
	 * @param $attribute
	 * @param callable $callback
	 */
	public function registerExtension($attribute, Closure $callback)
	{
		$this->extensions[$attribute] = $callback;
	}

	/**
	 * @param $finded
	 * @param $length
	 */
	protected function moveOpeningPositionBy(&$finded, $length)
	{
		$finded['opening']['start'] += $length;
		$finded['opening']['end'] += $length;
	}

}