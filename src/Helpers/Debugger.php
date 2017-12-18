<?php

namespace Contributte\Scheduler\Helpers;

use Exception;

class Debugger
{

	/**
	 * @param Exception $e
	 * @return void
	 */
	public static function log(Exception $e)
	{
		if (class_exists('\Tracy\Debugger')) {
			\Tracy\Debugger::log($e);
		}
	}

}
