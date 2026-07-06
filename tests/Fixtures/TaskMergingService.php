<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Scheduler\Attribute\AsCronTask;

class TaskMergingService
{

	#[AsCronTask(expression: '30 * * * *', schedule: 'default', arguments: ['merged'])]
	public function merge(): void
	{
		// noop
	}

}
