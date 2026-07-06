<?php declare(strict_types = 1);

namespace Contributte\Scheduler\DI\Pass;

use Contributte\Scheduler\Container\TaskServiceContainer;
use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Scheduler\Provider\TaskScheduleProvider;
use InvalidArgumentException;
use Nette\DI\Definitions\ServiceDefinition;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Console\Messenger\RunCommandMessageHandler;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;
use Symfony\Component\Scheduler\Messenger\ServiceCallMessage;
use Symfony\Component\Scheduler\Messenger\ServiceCallMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;

class TaskPass extends AbstractPass
{

	private const DEFAULT_METHOD = '__invoke';

	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('task.container'))
			->setFactory(TaskServiceContainer::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('serviceCallMessageHandler'))
			->setFactory(ServiceCallMessageHandler::class, ['@' . $this->prefix('task.container')])
			->setAutowired(false)
			->addTag(SchedulerExtension::HANDLER_TAG, ['handles' => ServiceCallMessage::class]);
	}

	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var ServiceDefinition $taskContainerDefinition */
		$taskContainerDefinition = $builder->getDefinition($this->prefix('task.container'));
		$taskContainerDefinition->setArgument(0, []);

		$tasksBySchedule = [];
		$taskServices = [];

		foreach ($builder->getDefinitions() as $serviceName => $definition) {
			$type = $definition->getType();

			if (!is_string($type) || !class_exists($type)) {
				continue;
			}

			$reflection = new ReflectionClass($type);

			foreach ($this->getClassLevelTasks($reflection, $serviceName) as $task) {
				$this->validateScheduleName($task['schedule']);
				$tasksBySchedule[$task['schedule']][] = $task['definition'];
				$taskServices[$serviceName] = $serviceName;
			}

			foreach ($reflection->getMethods() as $method) {
				foreach ($this->getMethodLevelTasks($method, $serviceName) as $task) {
					$this->validateScheduleName($task['schedule']);
					$tasksBySchedule[$task['schedule']][] = $task['definition'];
					$taskServices[$serviceName] = $serviceName;
				}
			}
		}

		ksort($taskServices);
		$taskContainerDefinition->setArgument(0, $taskServices);

		$this->registerRunCommandMessageHandler();

		/** @var ServiceDefinition $providerContainerDefinition */
		$providerContainerDefinition = $builder->getDefinition($this->prefix('provider.container'));
		$providers = $providerContainerDefinition->getFactory()->arguments[0] ?? [];

		if (!is_array($providers)) {
			$providers = [];
		}

		$configuredSchedules = [];

		foreach ($this->getConfig()->providers as $scheduleName => $providerConfig) {
			$normalizedProviderConfig = $this->normalizeProviderConfig($providerConfig);

			if ($normalizedProviderConfig['shorthand']) {
				continue;
			}

			$configuredSchedules[$scheduleName] = $normalizedProviderConfig;
		}

		$scheduleNames = array_unique(array_merge(array_keys($configuredSchedules), array_keys($tasksBySchedule)));
		sort($scheduleNames);

		foreach ($scheduleNames as $scheduleName) {
			$tasks = $tasksBySchedule[$scheduleName] ?? [];
			$providerConfig = $configuredSchedules[$scheduleName] ?? [
				'provider' => null,
				'lock' => null,
				'state' => null,
				'processOnlyLastMissedRun' => null,
				'shorthand' => false,
			];

			$hasInnerProvider = isset($providers[$scheduleName]) && is_string($providers[$scheduleName]);
			$hasOptions = $providerConfig['lock'] !== null
				|| $providerConfig['state'] !== null
				|| $providerConfig['processOnlyLastMissedRun'] !== null;

			if ($tasks === [] && $hasInnerProvider && !$hasOptions) {
				continue;
			}

			$innerProvider = isset($providers[$scheduleName]) && is_string($providers[$scheduleName])
				? '@' . $providers[$scheduleName]
				: null;
			$lock = $this->resolveTypedServiceReference($scheduleName, 'lock', $providerConfig['lock'], LockInterface::class);
			$state = $this->resolveTypedServiceReference($scheduleName, 'state', $providerConfig['state'], CacheInterface::class);

			$providerServiceName = $this->prefix('provider.task.' . $this->getServiceNameSuffix($scheduleName));
			$providerDefinition = $builder->addDefinition($providerServiceName)
				->setFactory(TaskScheduleProvider::class, [
					$innerProvider,
					$tasks,
					$lock,
					$state,
					$providerConfig['processOnlyLastMissedRun'],
				])
				->setAutowired(false);

			if (!$hasInnerProvider) {
				$providerDefinition->addTag(SchedulerExtension::PROVIDER_TAG, $scheduleName);
			}

			$providers[$scheduleName] = $providerServiceName;
		}

		ksort($providers);
		$providerContainerDefinition->setArgument(0, $providers);
	}

	/**
	 * @param class-string $requiredType
	 */
	private function resolveTypedServiceReference(string $scheduleName, string $option, ?string $serviceReference, string $requiredType): ?string
	{
		if ($serviceReference === null) {
			return null;
		}

		$serviceName = str_starts_with($serviceReference, '@')
			? substr($serviceReference, 1)
			: $serviceReference;

		if ($serviceName === '' || !$this->getContainerBuilder()->hasDefinition($serviceName)) {
			throw new InvalidArgumentException(sprintf('Provider %s scheduler > providers > %s > %s service "%s" was not found', $option, $scheduleName, $option, $serviceReference));
		}

		$definition = $this->getContainerBuilder()->getDefinition($serviceName);

		if (!is_string($definition->getType()) || !is_a($definition->getType(), $requiredType, true)) {
			throw new InvalidArgumentException(sprintf('Provider %s scheduler > providers > %s > %s service "%s" must implement %s', $option, $scheduleName, $option, $serviceReference, $requiredType));
		}

		return '@' . $serviceName;
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 * @return list<array{schedule: string, definition: array<string, mixed>}>
	 */
	private function getClassLevelTasks(ReflectionClass $reflection, string $serviceName): array
	{
		$tasks = [];
		$commandName = $this->resolveCommandName($serviceName);

		foreach ($reflection->getAttributes(AsCronTask::class) as $attribute) {
			$instance = $attribute->newInstance();

			if ($commandName === null) {
				$method = $instance->method ?? self::DEFAULT_METHOD;
				$this->validatePublicMethod($reflection, $serviceName, $method, AsCronTask::class);
			} else {
				$method = null;
			}

			$tasks[] = [
				'schedule' => $instance->schedule,
				'definition' => $commandName !== null ? [
					'messageType' => 'run_command',
					'command' => $commandName,
					'arguments' => $instance->arguments,
					'trigger' => 'cron',
					'expression' => $instance->expression,
					'timezone' => $instance->timezone,
					'jitter' => $instance->jitter,
					'transports' => $instance->transports,
				] : [
					'messageType' => 'service_call',
					'trigger' => 'cron',
					'expression' => $instance->expression,
					'timezone' => $instance->timezone,
					'jitter' => $instance->jitter,
					'service' => $serviceName,
					'method' => $method,
					'arguments' => (array) ($instance->arguments ?? []),
					'transports' => $instance->transports,
				],
			];
		}

		foreach ($reflection->getAttributes(AsPeriodicTask::class) as $attribute) {
			$instance = $attribute->newInstance();

			if ($commandName === null) {
				$method = $instance->method ?? self::DEFAULT_METHOD;
				$this->validatePublicMethod($reflection, $serviceName, $method, AsPeriodicTask::class);
			} else {
				$method = null;
			}

			$tasks[] = [
				'schedule' => $instance->schedule,
				'definition' => $commandName !== null ? [
					'messageType' => 'run_command',
					'command' => $commandName,
					'arguments' => $instance->arguments,
					'trigger' => 'every',
					'frequency' => $instance->frequency,
					'from' => $instance->from,
					'until' => $instance->until,
					'jitter' => $instance->jitter,
					'transports' => $instance->transports,
				] : [
					'messageType' => 'service_call',
					'trigger' => 'every',
					'frequency' => $instance->frequency,
					'from' => $instance->from,
					'until' => $instance->until,
					'jitter' => $instance->jitter,
					'service' => $serviceName,
					'method' => $method,
					'arguments' => (array) ($instance->arguments ?? []),
					'transports' => $instance->transports,
				],
			];
		}

		return $tasks;
	}

	/**
	 * @return list<array{schedule: string, definition: array<string, mixed>}>
	 */
	private function getMethodLevelTasks(ReflectionMethod $method, string $serviceName): array
	{
		$tasks = [];
		$commandName = $this->resolveCommandName($serviceName);

		foreach ($method->getAttributes(AsCronTask::class) as $attribute) {
			$instance = $attribute->newInstance();

			if ($commandName === null) {
				$this->validateMethodVisibility($serviceName, $method, AsCronTask::class);
			}

			$tasks[] = [
				'schedule' => $instance->schedule,
				'definition' => $commandName !== null ? [
					'messageType' => 'run_command',
					'command' => $commandName,
					'arguments' => $instance->arguments,
					'trigger' => 'cron',
					'expression' => $instance->expression,
					'timezone' => $instance->timezone,
					'jitter' => $instance->jitter,
					'transports' => $instance->transports,
				] : [
					'messageType' => 'service_call',
					'trigger' => 'cron',
					'expression' => $instance->expression,
					'timezone' => $instance->timezone,
					'jitter' => $instance->jitter,
					'service' => $serviceName,
					'method' => $method->getName(),
					'arguments' => (array) ($instance->arguments ?? []),
					'transports' => $instance->transports,
				],
			];
		}

		foreach ($method->getAttributes(AsPeriodicTask::class) as $attribute) {
			$instance = $attribute->newInstance();

			if ($commandName === null) {
				$this->validateMethodVisibility($serviceName, $method, AsPeriodicTask::class);
			}

			$tasks[] = [
				'schedule' => $instance->schedule,
				'definition' => $commandName !== null ? [
					'messageType' => 'run_command',
					'command' => $commandName,
					'arguments' => $instance->arguments,
					'trigger' => 'every',
					'frequency' => $instance->frequency,
					'from' => $instance->from,
					'until' => $instance->until,
					'jitter' => $instance->jitter,
					'transports' => $instance->transports,
				] : [
					'messageType' => 'service_call',
					'trigger' => 'every',
					'frequency' => $instance->frequency,
					'from' => $instance->from,
					'until' => $instance->until,
					'jitter' => $instance->jitter,
					'service' => $serviceName,
					'method' => $method->getName(),
					'arguments' => (array) ($instance->arguments ?? []),
					'transports' => $instance->transports,
				],
			];
		}

		return $tasks;
	}

	/**
	 * @param ReflectionClass<object> $reflection
	 * @param class-string $attributeClass
	 */
	private function validatePublicMethod(ReflectionClass $reflection, string $serviceName, string $method, string $attributeClass): void
	{
		if (!$reflection->hasMethod($method) || !$reflection->getMethod($method)->isPublic()) {
			throw new InvalidArgumentException(sprintf('Scheduler task service "%s" must define a public method "%s" for attribute %s.', $serviceName, $method, $attributeClass));
		}
	}

	/**
	 * @param class-string $attributeClass
	 */
	private function validateMethodVisibility(string $serviceName, ReflectionMethod $method, string $attributeClass): void
	{
		if (!$method->isPublic()) {
			throw new InvalidArgumentException(sprintf('Scheduler task service "%s" must define a public method "%s" for attribute %s.', $serviceName, $method->getName(), $attributeClass));
		}
	}

	private function registerRunCommandMessageHandler(): void
	{
		$builder = $this->getContainerBuilder();

		if ($builder->getByType(RunCommandMessageHandler::class, false) !== null) {
			return;
		}

		$applicationService = $builder->getByType(Application::class, false);

		if ($applicationService === null) {
			return;
		}

		$builder->addDefinition($this->prefix('runCommandMessageHandler'))
			->setFactory(RunCommandMessageHandler::class, ['@' . $applicationService])
			->setAutowired(false)
			->addTag(SchedulerExtension::HANDLER_TAG, ['handles' => RunCommandMessage::class]);
	}

	private function resolveCommandName(string $serviceName): ?string
	{
		$builder = $this->getContainerBuilder();
		$definition = $builder->getDefinition($serviceName);

		$type = $definition->getType();
		$tags = $definition->getTags();

		if (!isset($tags['console.command']) && (!is_string($type) || !is_a($type, Command::class, true))) {
			return null;
		}

		$commandName = null;

		if (isset($tags['console.command'])) {
			if (is_string($tags['console.command'])) {
				$commandName = $tags['console.command'];
			} elseif (is_array($tags['console.command'])) {
				$name = $tags['console.command']['name'] ?? null;

				if (is_string($name)) {
					$commandName = $name;
				}
			}
		}

		if ((!is_string($commandName) || $commandName === '') && is_string($type) && class_exists($type)) {
			$reflection = new ReflectionClass($type);
			$attributes = $reflection->getAttributes(AsCommand::class);

			if ($attributes !== []) {
				/** @var ReflectionAttribute<AsCommand> $attribute */
				$attribute = $attributes[0];
				$commandName = $attribute->newInstance()->name;
			} elseif (method_exists($type, 'getDefaultName')) {
				/** @var string|null $defaultName */
				$defaultName = $type::getDefaultName();
				$commandName = $defaultName;
			} elseif (property_exists($type, 'defaultName')) {
				$property = new ReflectionProperty($type, 'defaultName');
				$property->setAccessible(true);
				$value = $property->getValue();

				if (is_string($value)) {
					$commandName = $value;
				}
			}
		}

		if (!is_string($commandName) || $commandName === '') {
			throw new InvalidArgumentException(sprintf('Scheduler task command service "%s" must define a console command name.', $serviceName));
		}

		$aliases = explode('|', $commandName);
		$resolvedCommandName = array_shift($aliases);

		while ($resolvedCommandName === '' && $aliases !== []) {
			$resolvedCommandName = array_shift($aliases);
		}

		if ($resolvedCommandName === '') {
			throw new InvalidArgumentException(sprintf('Scheduler task command service "%s" must define a console command name.', $serviceName));
		}

		return $resolvedCommandName;
	}

}
