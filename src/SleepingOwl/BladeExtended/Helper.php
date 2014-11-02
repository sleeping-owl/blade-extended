<?php namespace SleepingOwl\BladeExtended;

class Helper
{

	/**
	 * @param string ...
	 * @return string
	 */
	public static function renderClass()
	{
		$class = self::filterArguments(func_get_args());
		if ( ! empty($class))
		{
			return implode(' ', $class) . ' ';
		}
		return '';
	}

	/**
	 * @param string ...
	 * @return string
	 */
	public static function renderAttribute()
	{
		$args = func_get_args();
		$attribute = array_shift($args);
		$values = self::filterArguments($args);
		if ( ! empty($values))
		{
			return ' ' . $attribute . '="' . implode(' ', $values) . '"';
		}
		return '';
	}

	/**
	 * Remove null, false and empty strings from array
	 *
	 * @param $arguments
	 * @return array
	 */
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