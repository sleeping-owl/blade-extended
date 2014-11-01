<?php namespace SleepingOwl\BladeExtended;

use \Blade;

class BladeExtended
{
	/**
	 * @var string
	 */
	protected $content;

	protected $macros = [
		'bd-foreach'       => 'Foreach',
		'bd-inner-foreach' => 'InnerForeach',
		'bd-if'            => 'If',
		'bd-class'         => 'Class',
	];

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
	 * @param mixed $content
	 */
	public function setContent($content)
	{
		$this->content = $content;
	}

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
		return $this->content;
	}

	protected function parseIf($finded)
	{
		$this->wrapOuterContent($finded, '@if(:value)', '@endif ');
		return true;
	}

	protected function parseClass($finded)
	{
		$value = $finded['value'];
		$value = preg_replace('~(.+?\?[^:]+?)($|,)~', '\1 : NULL\2', $value);

		if (preg_match('~\sclass="(?<class>.*?)"~', $this->content, $matches, PREG_OFFSET_CAPTURE, $finded['start']))
		{
			$class = '{{ \SleepingOwl\BladeExtended\Helper::renderClass(' . $value . ') }}';
			$this->insertContent($matches['class'][1], $class);
			return true;
		}
		$class = '{{ \SleepingOwl\BladeExtended\Helper::renderClassFull(' . $value . ') }}';
		$this->replaceAttribute('bd-class', $class, $finded['start']);
		return false;
	}

	protected function parseForeach($finded)
	{
		$this->wrapOuterContent($finded, '@foreach(:value)', '@endforeach ');
		return true;
	}

	protected function parseInnerForeach($finded)
	{
		$this->wrapInnerContent($finded, '@foreach(:value)', '@endforeach ');
		return true;
	}

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

	protected function insertContent($position, $string)
	{
		$this->content = substr($this->content, 0, $position) . $string . substr($this->content, $position);
	}

	protected function replaceAttribute($attribute, $replacement, $start)
	{
		$this->content = preg_replace('~\s?' . $attribute . '=".+?"~', $replacement, $this->content, 1, $start);
	}

	protected function deleteAttribute($attribute, $start)
	{
		$this->replaceAttribute($attribute, '', $start);
	}

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