<?php declare(strict_types = 1);

namespace Tests\Contributte\Scheduler\DI;

use Contributte\Scheduler\IJob;
use DateTime;

final class CustomJob implements IJob
{

	public function isDue(DateTime $dateTime): bool
	{
		return true;
	}

	public function run(): void
	{
	}

}
