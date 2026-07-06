<?php declare(strict_types = 1);

namespace Contributte\Scheduler\DI;

use Contributte\Scheduler\CallbackJob;
use Contributte\Scheduler\Command\ForceRunCommand;
use Contributte\Scheduler\Command\HelpCommand;
use Contributte\Scheduler\Command\ListCommand;
use Contributte\Scheduler\Command\RunCommand;
use Contributte\Scheduler\DI\Pass\AbstractPass;
use Contributte\Scheduler\DI\Pass\ConsolePass;
use Contributte\Scheduler\DI\Pass\EventPass;
use Contributte\Scheduler\DI\Pass\ProviderPass;
use Contributte\Scheduler\DI\Pass\TaskPass;
use Contributte\Scheduler\DI\Pass\TransportPass;
use Contributte\Scheduler\IScheduler;
use Contributte\Scheduler\LockingScheduler;
use Contributte\Scheduler\Scheduler;
use InvalidArgumentException;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\Statement;
use Nette\DI\Extensions\InjectExtension;
use Nette\PhpGenerator\ClassType;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use stdClass;

/**
 * @property-read stdClass $config
 */
class SchedulerExtension extends CompilerExtension
{

	public const PROVIDER_TAG = 'contributte.scheduler.provider';
	public const TRANSPORT_FACTORY_TAG = 'contributte.messenger.transport_factory';
	public const TRANSPORT_TAG = 'contributte.messenger.transport';
	public const HANDLER_TAG = 'contributte.messenger.handler';

	/** @var AbstractPass[] */
	private array $passes = [];

	public function __construct()
	{
		$this->passes[] = new ProviderPass($this);
		$this->passes[] = new TaskPass($this);
		$this->passes[] = new TransportPass($this);
		$this->passes[] = new EventPass($this);
		$this->passes[] = new ConsolePass($this);
	}

	public function getConfigSchema(): Schema
	{
		$providerConfigSchema = Expect::anyOf(
			Expect::string(),
			Expect::structure([
				'provider' => Expect::string()->nullable(),
				'lock' => Expect::string()->nullable(),
				'state' => Expect::string()->nullable(),
				'processOnlyLastMissedRun' => Expect::bool()->nullable(),
			]),
		);

		return Expect::structure([
			'path' => Expect::string()->nullable(),
			'jobs' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), Expect::array())
			)->default([]),
			'providers' => Expect::arrayOf(
				$providerConfigSchema,
				Expect::string()->required(),
			)->default([]),
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
			// 1. Array config
			if (is_array($jobConfig)) {
				// Callback job with cron expression
				if (isset($jobConfig['cron']) || isset($jobConfig['callback'])) {
					if (!isset($jobConfig['cron'], $jobConfig['callback'])) {
						throw new InvalidArgumentException(sprintf('Both options "callback" and "cron" of %s > jobs > %s must be configured', $this->name, $jobName));
					}

					$jobDefinition = new Statement(CallbackJob::class, [$jobConfig['cron'], $jobConfig['callback']]);
				} else {
					// Class config with optional inject
					$class = $jobConfig['class'] ?? null;
					if ($class === null || !is_string($class)) {
						throw new InvalidArgumentException(sprintf('Option "class" of %s > jobs > %s must be configured', $this->name, $jobName));
					}

					$inject = (bool) ($jobConfig['inject'] ?? false);

					$jobDefinition = $builder->addDefinition($this->prefix('job.' . $jobName))
						->setFactory($class)
						->setAutowired(false);

					if ($inject) {
						$jobDefinition->addTag(InjectExtension::TagInject);
					}
				}
			// 2. String config (class name or service reference)
			} elseif (is_string($jobConfig)) {
				$jobDefinition = $builder->addDefinition($this->prefix('job.' . $jobName))
					->setFactory($jobConfig)
					->setAutowired(false);
			// 3. Invalid config
			} else {
				throw new InvalidArgumentException(sprintf('Job %s > jobs > %s must be a string or array', $this->name, $jobName));
			}

			$schedulerDefinition->addSetup('add', [$jobDefinition, $jobName]);
		}

		foreach ($this->passes as $pass) {
			$pass->loadPassConfiguration();
		}
	}

	public function beforeCompile(): void
	{
		foreach ($this->passes as $pass) {
			$pass->beforePassCompile();
		}
	}

	public function afterCompile(ClassType $class): void
	{
		foreach ($this->passes as $pass) {
			$pass->afterPassCompile($class);
		}
	}

}
