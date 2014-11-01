<?php namespace SleepingOwl\BladeExtended;

class Helper
{

	public static function renderClass()
	{
		$class = self::filterArguments(func_get_args());
		if ( ! empty($class))
		{
			return implode(' ', $class) . ' ';
		}
		return '';
	}

	public static function renderClassFull()
	{
		$class = self::filterArguments(func_get_args());
		if ( ! empty($class))
		{
			return ' class="' . implode(' ', $class) . '"';
		}
		return '';
	}

	protected static function filterArguments($arguments)
	{
		$class = array_filter($arguments, function ($el)
		{
			if (is_null($el)) return false;
			if ($el === false) return false;
			if (trim($el) === '') return false;
			return true;
		});
		return $class;
	}

} 