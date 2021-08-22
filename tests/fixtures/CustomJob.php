<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Contributte\Scheduler\IJob;
use DateTime;
use DateTimeInterface;

final class CustomJob implements IJob
{

	public function isDue(DateTime $dateTime, DateTimeInterface $lastCheck = null): bool
	{
		return true;
	}

	public function run(): void
	{
	}

}
