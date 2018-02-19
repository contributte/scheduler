<?php

declare(strict_types = 1);

namespace Contributte\Scheduler\Helpers;

use Exception;

class Debugger
{

	public static function log(Exception $e): void
	{
		if (class_exists('\Tracy\Debugger')) {
			\Tracy\Debugger::log($e);
		}
	}

}
