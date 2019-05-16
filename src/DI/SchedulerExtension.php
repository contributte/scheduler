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
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class SchedulerExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			 'path' => Expect::string('%tempDir%/scheduler'),
			 'jobs' => Expect::array(),
		]);
	}

	/**
	 * Register services
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = (array) $this->config;
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
