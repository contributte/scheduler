<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsPeriodicTask(frequency: 60, schedule: 'execution', method: 'record', arguments: ['scheduled'])]
class TaskExecutionService
{

	/** @var list<string> */
	public array $calls = [];

	public function record(string $value): void
	{
		$this->calls[] = $value;
	}

}
