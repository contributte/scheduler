<?php declare(strict_types = 1);

namespace Contributte\Scheduler;

use DateTime;
use DateTimeInterface;

interface IJob
{

	public function isDue(DateTime $dateTime, DateTimeInterface $lastCheck = null): bool;

	public function run(): void;

}
