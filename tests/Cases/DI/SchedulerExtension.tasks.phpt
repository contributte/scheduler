<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Scheduler\Container\ScheduleProviderContainer;
use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\Messenger\SchedulerTransport;
use Symfony\Component\Scheduler\Messenger\SchedulerTransportFactory;
use Symfony\Component\Scheduler\Messenger\ServiceCallMessage;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Tester\Assert;
use Tests\Fixtures\TaskExecutionService;
use Tests\Fixtures\TaskGeneratedCommand;
use Tests\Fixtures\TestLock;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				taskGeneratedService: Tests\Fixtures\TaskGeneratedService
				providerLock: Tests\Fixtures\TestLock
				providerState: Symfony\Component\Cache\Adapter\ArrayAdapter

			scheduler:
				providers:
					generated:
						lock: @providerLock
						state: @providerState
						processOnlyLastMissedRun: true
			NEON
			));
		})->build();

	$providerContainer = $container->getService('scheduler.provider.container');
	Assert::type(ScheduleProviderContainer::class, $providerContainer);
	Assert::true($providerContainer->has('generated'));

	$provider = $providerContainer->get('generated');
	Assert::type(ScheduleProviderInterface::class, $provider);
	Assert::type(TestLock::class, $provider->getSchedule()->getLock());
	Assert::type(ArrayAdapter::class, $provider->getSchedule()->getState());
	Assert::true($provider->getSchedule()->shouldProcessOnlyLastMissedRun());

	$messages = getScheduledMessages($provider->getSchedule()->getRecurringMessages());
	Assert::count(2, $messages);
	$firstMessage = $messages[0];
	$secondMessage = $messages[1];
	Assert::type(ServiceCallMessage::class, $firstMessage);
	Assert::type(RedispatchMessage::class, $secondMessage);

	if (!$firstMessage instanceof ServiceCallMessage || !$secondMessage instanceof RedispatchMessage || !$secondMessage->envelope instanceof ServiceCallMessage) {
		throw new \LogicException('Unexpected scheduled message type.');
	}

	Assert::same('taskGeneratedService', $firstMessage->getServiceId());
	Assert::same('__invoke', $firstMessage->getMethod());
	Assert::same('async', $secondMessage->transportNames);
	Assert::same('taskGeneratedService', $secondMessage->envelope->getServiceId());
	Assert::same('hourly', $secondMessage->envelope->getMethod());

	$transportFactory = $container->getService('scheduler.transportFactory');
	Assert::type(SchedulerTransportFactory::class, $transportFactory);
	Assert::type(SchedulerTransport::class, $container->getService('scheduler.transport.generated'));
});

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				taskGeneratedCommand: Tests\Fixtures\TaskGeneratedCommand
			NEON
			));
		})->build();

	$providerContainer = $container->getService('scheduler.provider.container');
	Assert::type(ScheduleProviderContainer::class, $providerContainer);
	Assert::true($providerContainer->has('command-tasks'));

	$provider = $providerContainer->get('command-tasks');
	$messages = getScheduledMessages($provider->getSchedule()->getRecurringMessages());
	Assert::count(2, $messages);
	Assert::type(RunCommandMessage::class, $messages[0]);
	Assert::type(RedispatchMessage::class, $messages[1]);

	if (!$messages[0] instanceof RunCommandMessage || !$messages[1] instanceof RedispatchMessage || !$messages[1]->envelope instanceof RunCommandMessage) {
		throw new \LogicException('Unexpected command task message type.');
	}

	Assert::same('app:generated from-class', $messages[0]->input);
	Assert::contains('app:generated', $messages[1]->envelope->input);
	Assert::contains('from-array', $messages[1]->envelope->input);
	Assert::same('async', $messages[1]->transportNames);
});

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				defaultScheduleProvider: Tests\Fixtures\DefaultScheduleProvider
				taskMergingService: Tests\Fixtures\TaskMergingService

			scheduler:
				providers:
					default: @defaultScheduleProvider
			NEON
			));
		})->build();

	$providerContainer = $container->getService('scheduler.provider.container');
	$provider = $providerContainer->get('default');

	Assert::count(2, $provider->getSchedule()->getRecurringMessages());
	Assert::count(1, $container->getService('defaultScheduleProvider')->getSchedule()->getRecurringMessages());
});

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addExtension('messenger', new MessengerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				taskExecutionService: Tests\Fixtures\TaskExecutionService
			NEON
			));
		})->build();

	$bus = $container->getService('messenger.bus.messageBus.bus');
	Assert::type(MessageBusInterface::class, $bus);

	$bus->dispatch(new ServiceCallMessage('taskExecutionService', 'record', ['from-bus']));

	$service = $container->getService('taskExecutionService');
	Assert::type(TaskExecutionService::class, $service);
	Assert::same(['from-bus'], $service->calls);
});

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addExtension('messenger', new MessengerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				application:
					factory: Symfony\Component\Console\Application
					setup:
						- setAutoExit(false)
						- add(@taskGeneratedCommand)

				taskGeneratedCommand: Tests\Fixtures\TaskGeneratedCommand
			NEON
			));
		})->build();

	$bus = $container->getService('messenger.bus.messageBus.bus');
	Assert::type(MessageBusInterface::class, $bus);

	$bus->dispatch(new RunCommandMessage('app:generated from-bus'));

	$command = $container->getService('taskGeneratedCommand');
	Assert::type(TaskGeneratedCommand::class, $command);
	Assert::same(['from-bus'], $command->executions);
	Assert::type(Application::class, $container->getService('application'));
});

Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('scheduler', new SchedulerExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
					services:
						invalidTaskService: Tests\Fixtures\InvalidTaskService
					NEON
					));
				})->build();
		},
		\InvalidArgumentException::class,
		'Scheduler task service "invalidTaskService" must define a public method "missing" for attribute Symfony\Component\Scheduler\Attribute\AsCronTask.',
	);
});

/**
 * @param list<RecurringMessage> $recurringMessages
 * @return list<ServiceCallMessage|RunCommandMessage|RedispatchMessage>
 */
function getScheduledMessages(array $recurringMessages): array
{
	$messages = [];

	foreach ($recurringMessages as $recurringMessage) {
		$message = iterator_to_array($recurringMessage->getMessages(new MessageContext(
			'test',
			$recurringMessage->getId(),
			$recurringMessage->getTrigger(),
			new \DateTimeImmutable(),
		)))[0];

		if (!$message instanceof ServiceCallMessage && !$message instanceof RunCommandMessage && !$message instanceof RedispatchMessage) {
			throw new \LogicException('Unexpected scheduled message type.');
		}

		$messages[] = $message;
	}

	return $messages;
}
