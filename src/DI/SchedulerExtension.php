<?php declare(strict_types = 1);

namespace Contributte\Scheduler\DI;

use Contributte\Scheduler\CallbackJob;
use Contributte\Scheduler\Command\ForceRunCommand;
use Contributte\Scheduler\Command\HelpCommand;
use Contributte\Scheduler\Command\ListCommand;
use Contributte\Scheduler\Command\RunCommand;
use Contributte\Scheduler\IScheduler;
use Contributte\Scheduler\LockingScheduler;
use Contributte\Scheduler\Scheduler;
use InvalidArgumentException;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;

/**
 * @property-read stdClass $config
 */
class SchedulerExtension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'path' => Expect::string()->nullable(),
			'jobs' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), Expect::array(), Expect::type(Statement::class))
			),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		// Scheduler
		$schedulerDefinition = $builder->addDefinition($this->prefix('scheduler'))
			->setType(IScheduler::class);
		if ($config->path !== null) {
			$schedulerDefinition->setFactory(LockingScheduler::class, [$config->path]);
		} else {
			$schedulerDefinition->setFactory(Scheduler::class);
		}

		// Commands
		$builder->addDefinition($this->prefix('runCommand'))
			->setFactory(RunCommand::class)
			->setAutowired(false);
		$builder->addDefinition($this->prefix('forceRunCommand'))
			->setFactory(ForceRunCommand::class)
			->setAutowired(false);
		$builder->addDefinition($this->prefix('listCommand'))
			->setFactory(ListCommand::class)
			->setAutowired(false);
		$builder->addDefinition($this->prefix('helpCommand'))
			->setFactory(HelpCommand::class)
			->setAutowired(false);

		// Jobs
		foreach ($config->jobs as $jobName => $jobConfig) {
			if (is_array($jobConfig) && (isset($jobConfig['cron']) || isset($jobConfig['callback']))) {
				if (!isset($jobConfig['cron'], $jobConfig['callback'])) {
					throw new InvalidArgumentException(sprintf('Both options "callback" and "cron" of %s > jobs > %s must be configured', $this->name, $jobName));
				}

				$jobDefinition = new Statement(CallbackJob::class, [$jobConfig['cron'], $jobConfig['callback']]);
			} else {
				$jobDefinition = $builder->addDefinition($this->prefix('job.' . $jobName))
					->setFactory($jobConfig)
					->setAutowired(false);
			}

			$schedulerDefinition->addSetup('add', [$jobDefinition, $jobName]);
		}
	}

}
