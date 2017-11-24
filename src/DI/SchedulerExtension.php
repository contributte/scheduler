<?php

namespace Tlapnet\Scheduler\DI;

use Nette\DI\CompilerExtension;
use Tlapnet\Scheduler\Job;
use Tlapnet\Scheduler\Scheduler;
use Tlapnet\Scheduler\SchedulerCommand;

class SchedulerExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'jobs' => [],
	];

	/**
	 * Register services
	 *
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);
		$scheduler = $builder->addDefinition($this->prefix('scheduler'))
			->setClass(Scheduler::class);
		$builder->addDefinition($this->prefix('schedulerCommand'))
			->setClass(SchedulerCommand::class)
			->setAutowired(FALSE);
		foreach ($config['jobs'] as $jobConfig) {
			$job = new Job($jobConfig['cron'], $jobConfig['callback']);
			$scheduler->addSetup('addJob', [$job]);
		}
	}

}
