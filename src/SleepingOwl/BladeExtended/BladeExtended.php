<?php namespace SleepingOwl\BladeExtended;

use \Blade;

class BladeExtended
{
	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var array
	 */
	protected $macros = [
		'bd-foreach'       => 'Foreach',
		'bd-inner-foreach' => 'InnerForeach',
		'bd-if'            => 'If',
		'bd-class'         => 'Class',
	];

	/**
	 * Register Blade extenstion
	 */
	public static function register()
	{
		$me = new static;
		Blade::extend(function ($content) use ($me)
		{
			$me->setContent($content);
			return $me->parse();
		});
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
		foreach ($this->macros as $attribute => $method)
		{
			while ($finded = $this->find($attribute))
			{
				if ($this->{'parse' . $method}($finded))
				{
					$this->deleteAttribute($attribute, $finded['start']);
				}
			}
		}
		//return $this->content;
		echo $this->content;die;
	}

	/**
	 * @param $finded
	 * @return bool
	 */
	protected function parseIf($finded)
	{
		$this->wrapOuterContent($finded, '@if(:value)', '@endif ');
		return true;
	}

	/**
	 * @param $finded
	 * @return bool
	 */
	protected function parseClass($finded)
	{
		$value = $finded['value'];
		$value = preg_replace('~(.+?\?[^:]+?)($|,)~', '\1 : NULL\2', $value);

		$tag = substr($this->content, $finded['start'], $finded['offset'] - $finded['start']);
		if (preg_match('~\sclass="(?<class>.*?)"~', $tag, $matches, PREG_OFFSET_CAPTURE))
		{
			$class = '{{ \SleepingOwl\BladeExtended\Helper::renderClass(' . $value . ') }}';
			$this->insertContent($finded['start'] + $matches['class'][1], $class);
			return true;
		}
		$class = '{{ \SleepingOwl\BladeExtended\Helper::renderClassFull(' . $value . ') }}';
		$this->replaceAttribute('bd-class', $class, $finded['start']);
		return false;
	}

	/**
	 * @param $finded
	 * @return bool
	 */
	protected function parseForeach($finded)
	{
		$this->wrapOuterContent($finded, '@foreach(:value)', '@endforeach ');
		return true;
	}

	/**
	 * @param $finded
	 * @return bool
	 */
	protected function parseInnerForeach($finded)
	{
		$this->wrapInnerContent($finded, '@foreach(:value)', '@endforeach ');
		return true;
	}

	/**
	 * Find tag with $attribute
	 *
	 * @param $attribute
	 * @return array|bool
	 */
	protected function find($attribute)
	{
		if ( ! preg_match('~<(?<tagname>[a-zA-Z]+).*?\s?' . $attribute . '="(?<value>.+?)".*?/?>~', $this->content, $matches, PREG_OFFSET_CAPTURE))
		{
			return false;
		}
		return [
			'tagname' => $matches['tagname'][0],
			'value'   => $matches['value'][0],
			'start'   => $matches[0][1],
			'offset'  => $matches[0][1] + strlen($matches[0][0])
		];
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
			return ['inner' => $offset, 'outer' => $offset];
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
			'inner' => $innerEnd,
			'outer' => $offset
		];
	}

	/**
	 * Insert $string to result at $position
	 *
	 * @param $position
	 * @param $string
	 */
	protected function insertContent($position, $string)
	{
		$this->content = substr($this->content, 0, $position) . $string . substr($this->content, $position);
	}

	/**
	 * Replace whole tag attribute with $replacement
	 *
	 * @param $attribute
	 * @param $replacement
	 * @param $start
	 */
	protected function replaceAttribute($attribute, $replacement, $start)
	{
		$this->content = preg_replace('~\s?' . $attribute . '=".+?"~', $replacement, $this->content, 1, $start);
	}

	/**
	 * Delete tag attribute
	 *
	 * @param $attribute
	 * @param $start
	 */
	protected function deleteAttribute($attribute, $start)
	{
		$this->replaceAttribute($attribute, '', $start);
	}

	/**
	 * Wrap tag with $before and $after
	 *
	 * @param $finded
	 * @param $before
	 * @param $after
	 */
	protected function wrapOuterContent($finded, $before, $after)
	{
		$tagname = $finded['tagname'];
		$value = $finded['value'];
		$start = $finded['start'];
		$offset = $finded['offset'];
		$insertion = strtr($before, [':value' => $value]);
		$this->insertContent($start, $insertion);
		$closingPosition = $this->findTagClosingPosition($tagname, $offset + strlen($insertion));
		$this->insertContent($closingPosition['outer'], $after);
	}

	/**
	 * Wrap tag inner content with $before and $after
	 *
	 * @param $finded
	 * @param $before
	 * @param $after
	 */
	protected function wrapInnerContent($finded, $before, $after)
	{
		$tagname = $finded['tagname'];
		$value = $finded['value'];
		$offset = $finded['offset'];
		$insertion = strtr($before, [':value' => $value]);
		$this->insertContent($offset, $insertion);
		$closingPosition = $this->findTagClosingPosition($tagname, $offset + strlen($insertion));
		$this->insertContent($closingPosition['inner'], $after);
	}

}