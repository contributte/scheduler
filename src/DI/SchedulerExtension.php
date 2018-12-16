<?php declare(strict_types = 1);

namespace Contributte\Scheduler\DI;

use Contributte\Scheduler\CallbackJob;
use Contributte\Scheduler\Command\HelpCommand;
use Contributte\Scheduler\Command\ListCommand;
use Contributte\Scheduler\Command\RunCommand;
use Contributte\Scheduler\LockingScheduler;
use Nette\DI\CompilerExtension;
use Nette\DI\Helpers;
use Nette\DI\Statement;

class SchedulerExtension extends CompilerExtension
{

	/** @var mixed[] */
	private $defaults = [
		'path' => '%tempDir%/scheduler',
		'jobs' => [],
	];

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);
		$config = Helpers::expand($config, $builder->parameters);

		// Scheduler
		$scheduler = $builder->addDefinition($this->prefix('scheduler'))
			->setFactory(LockingScheduler::class, [$config['path']]);

		// Commands
		$builder->addDefinition($this->prefix('runCommand'))
			->setFactory(RunCommand::class)
			->setAutowired(false);
		$builder->addDefinition($this->prefix('listCommand'))
			->setFactory(ListCommand::class)
			->setAutowired(false);
		$builder->addDefinition($this->prefix('helpCommand'))
			->setFactory(HelpCommand::class)
			->setAutowired(false);

		// Jobs
		foreach ($config['jobs'] as $key => $job) {
			if (is_array($job)) {
				$job = new Statement(CallbackJob::class, [$job['cron'], $job['callback']]);
			} else {
				$job = new Statement($job);
			}

			$scheduler->addSetup('add', [$job, $key]);
		}
	}

}
