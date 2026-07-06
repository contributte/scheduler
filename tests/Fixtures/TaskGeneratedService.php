<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: 3600, schedule: 'generated')]
class TaskGeneratedService
{

	#[AsCronTask(expression: '15 * * * *', schedule: 'generated', transports: 'async')]
	public function hourly(): void
	{
		// noop
	}

	public function __invoke(): void
	{
		// noop
	}

}
