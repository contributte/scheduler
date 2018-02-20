<?php

declare(strict_types = 1);

namespace Contributte\Scheduler;

use DateTimeInterface;

interface IJob
{

	public function isDue(DateTimeInterface $dateTime): bool;

	public function run(): void;

}
