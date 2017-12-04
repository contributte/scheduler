<?php

namespace Tlapnet\Scheduler\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Statement;
use Tlapnet\Scheduler\CallbackJob;
use Tlapnet\Scheduler\Command\ListCommand;
use Tlapnet\Scheduler\Command\RunCommand;
use Tlapnet\Scheduler\Scheduler;

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

		// Scheduler
		$scheduler = $builder->addDefinition($this->prefix('scheduler'))
			->setClass(Scheduler::class);

		// Commands
		$builder->addDefinition($this->prefix('runCommand'))
			->setClass(RunCommand::class)
			->setAutowired(FALSE);
		$builder->addDefinition($this->prefix('listCommand'))
			->setClass(ListCommand::class)
			->setAutowired(FALSE);

		// Jobs
		foreach ($config['jobs'] as $job) {
			if (is_array($job)) {
				$job = new Statement(CallbackJob::class, [$job['cron'], $job['callback']]);
			} else {
				$job = new Statement($job);
			}
			$scheduler->addSetup('add', [$job]);
		}
	}

}
