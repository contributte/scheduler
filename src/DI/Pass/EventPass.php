<?php declare(strict_types = 1);

namespace Contributte\Scheduler\DI\Pass;

use Nette\DI\Definitions\ServiceDefinition;
use Nette\PhpGenerator\ClassType;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Scheduler\EventListener\DispatchSchedulerEventListener;

class EventPass extends AbstractPass
{

	private const MESSENGER_EVENT_DISPATCHER = 'messenger.event.dispatcher';

	private ?string $listenerDispatcherServiceName = null;

	public function loadPassConfiguration(): void
	{
		if (!class_exists(DispatchSchedulerEventListener::class)) {
			return;
		}

		$this->getContainerBuilder()->addDefinition($this->prefix('event.dispatcher'))
			->setFactory(EventDispatcher::class)
			->setAutowired([EventDispatcherInterface::class]);
	}

	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$this->listenerDispatcherServiceName = null;

		if (!class_exists(DispatchSchedulerEventListener::class) || !$builder->hasDefinition($this->prefix('event.dispatcher'))) {
			return;
		}

		$dispatcherServiceName = null;

		foreach ($builder->findByType(EventDispatcherInterface::class) as $serviceName => $definition) {
			if (!is_string($serviceName)) {
				continue;
			}

			if ($serviceName === $this->prefix('event.dispatcher')) {
				continue;
			}

			if ($serviceName === self::MESSENGER_EVENT_DISPATCHER) {
				continue;
			}

			$dispatcherServiceName = $serviceName;

			break;
		}

		/** @var ServiceDefinition $schedulerDispatcherDefinition */
		$schedulerDispatcherDefinition = $builder->getDefinition($this->prefix('event.dispatcher'));

		if ($dispatcherServiceName !== null && $dispatcherServiceName !== $this->prefix('event.dispatcher')) {
			$schedulerDispatcherDefinition->setFactory('@' . $dispatcherServiceName)
				->setAutowired(false);
		} else {
			$dispatcherServiceName = $this->prefix('event.dispatcher');
		}

		$this->listenerDispatcherServiceName = $dispatcherServiceName;

		$builder->addDefinition($this->prefix('dispatchSchedulerEventListener'))
			->setFactory(DispatchSchedulerEventListener::class, [
				'@' . $this->prefix('provider.container'),
				'@' . $this->prefix('event.dispatcher'),
			])
			->setAutowired(false);
	}

	public function afterPassCompile(ClassType $class): void
	{
		if ($this->listenerDispatcherServiceName === null) {
			return;
		}

		$dispatcherServiceName = $this->getContainerBuilder()->hasDefinition(self::MESSENGER_EVENT_DISPATCHER)
			? self::MESSENGER_EVENT_DISPATCHER
			: $this->listenerDispatcherServiceName;

		$initialize = $class->getMethod('initialize');
		$initialize->addBody('$this->getService(?)->addSubscriber($this->getService(?));', [
			$dispatcherServiceName,
			$this->prefix('dispatchSchedulerEventListener'),
		]);
	}

}
