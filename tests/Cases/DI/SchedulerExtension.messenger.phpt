<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Messenger\Container\NetteContainer;
use Contributte\Messenger\DI\MessengerExtension;
use Contributte\Scheduler\Container\ScheduleProviderContainer;
use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Scheduler\Event\FailureEvent;
use Symfony\Component\Scheduler\Event\PostRunEvent;
use Symfony\Component\Scheduler\Event\PreRunEvent;
use Symfony\Component\Scheduler\EventListener\DispatchSchedulerEventListener;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\Messenger\ScheduledStamp;
use Symfony\Component\Scheduler\Messenger\SchedulerTransport;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addExtension('messenger', new MessengerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			scheduler:
				providers:
					default: Tests\Fixtures\DefaultScheduleProvider

			messenger:
				routing:
					stdClass: [scheduler_default]
			NEON
			));
		})->build();

	$container->initialize();

	$transportContainer = $container->getService('messenger.transport.container');
	Assert::type(NetteContainer::class, $transportContainer);
	Assert::true($transportContainer->has('scheduler_default'));

	$transport = $transportContainer->get('scheduler_default');
	Assert::type(SchedulerTransport::class, $transport);
	Assert::same($container->getService('scheduler.transport.default'), $transport);
	Assert::type(EventDispatcher::class, $container->getService('scheduler.event.dispatcher'));
	Assert::true(in_array(DispatchSchedulerEventListener::class, getListenerClasses($container->getService('messenger.event.dispatcher')), true));
});

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addExtension('messenger', new MessengerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				applicationEventDispatcher: Symfony\Component\EventDispatcher\EventDispatcher

			scheduler:
				providers:
					default: Tests\Fixtures\DefaultScheduleProvider
			NEON
			));
		})->build();

	$container->initialize();

	$messengerDispatcher = $container->getService('messenger.event.dispatcher');
	$applicationDispatcher = $container->getService('applicationEventDispatcher');
	$schedulerDispatcher = $container->getService('scheduler.event.dispatcher');

	Assert::type(EventDispatcher::class, $messengerDispatcher);
	Assert::same($applicationDispatcher, $schedulerDispatcher);
	Assert::type(DispatchSchedulerEventListener::class, $container->getService('scheduler.dispatchSchedulerEventListener'));
	Assert::true(in_array(DispatchSchedulerEventListener::class, getListenerClasses($messengerDispatcher), true));

	$providerContainer = $container->getService('scheduler.provider.container');
	Assert::type(ScheduleProviderContainer::class, $providerContainer);
	$provider = $providerContainer->get('default');
	$recurringMessage = $provider->getSchedule()->getRecurringMessages()[0];
	$context = new MessageContext(
		'default',
		$recurringMessage->getId(),
		$recurringMessage->getTrigger(),
		new \DateTimeImmutable(),
	);
	$message = iterator_to_array($recurringMessage->getMessages($context))[0];
	$envelope = new Envelope($message, [new ScheduledStamp($context)]);

	$state = new class
	{

		/** @var list<array{string, string, mixed}> */
		public array $events = [];

	};
	$applicationDispatcher->addListener(PreRunEvent::class, static function (PreRunEvent $event) use ($state): void {
		$state->events[] = ['pre', $event->getMessageContext()->name, $event->getMessage()::class];
	});
	$applicationDispatcher->addListener(PostRunEvent::class, static function (PostRunEvent $event) use ($state): void {
		$state->events[] = ['post', $event->getMessageContext()->name, $event->getResult()];
	});
	$applicationDispatcher->addListener(FailureEvent::class, static function (FailureEvent $event) use ($state): void {
		$state->events[] = ['failure', $event->getMessageContext()->name, $event->getError()::class];
	});

	$messengerDispatcher->dispatch(new WorkerMessageReceivedEvent($envelope, 'scheduler_default'));
	$messengerDispatcher->dispatch(new WorkerMessageHandledEvent($envelope->with(new HandledStamp('done', 'handler')), 'scheduler_default'));
	$messengerDispatcher->dispatch(new WorkerMessageFailedEvent($envelope, 'scheduler_default', new \RuntimeException('failed')));

	Assert::same([
		['pre', 'default', \stdClass::class],
		['post', 'default', 'done'],
		['failure', 'default', \RuntimeException::class],
	], $state->events);
});

/**
 * @return list<string>
 */
function getListenerClasses(EventDispatcher $dispatcher): array
{
	$listeners = [];

	foreach ($dispatcher->getListeners() as $listenersForEvent) {
		if (!is_array($listenersForEvent)) {
			continue;
		}

		foreach ($listenersForEvent as $listenerForEvent) {
			$listeners[] = $listenerForEvent[0]::class;
		}
	}

	return $listeners;
}
