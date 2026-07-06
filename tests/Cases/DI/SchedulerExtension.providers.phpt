<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Scheduler\Container\ScheduleProviderContainer;
use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Scheduler\Messenger\SchedulerTransport;
use Symfony\Component\Scheduler\Messenger\SchedulerTransportFactory;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Tester\Assert;
use Tests\Fixtures\AttributedDefaultScheduleProvider;
use Tests\Fixtures\AttributedScheduleProvider;
use Tests\Fixtures\AttributedTaggedScheduleProvider;
use Tests\Fixtures\DefaultScheduleProvider;
use Tests\Fixtures\TestLock;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				existingProvider: Tests\Fixtures\DefaultScheduleProvider
				providerLock: Tests\Fixtures\TestLock
				providerState: Symfony\Component\Cache\Adapter\ArrayAdapter
				attributedProvider: Tests\Fixtures\AttributedScheduleProvider
				attributedDefaultProvider: Tests\Fixtures\AttributedDefaultScheduleProvider
				manualTaggedProvider:
					class: Tests\Fixtures\DefaultScheduleProvider
					tags:
						contributte.scheduler.provider: tagged
				attributedTaggedProvider: Tests\Fixtures\AttributedTaggedScheduleProvider

			scheduler:
				providers:
					default: Tests\Fixtures\DefaultScheduleProvider
					existing: @existingProvider
					configured:
						provider: @existingProvider
						lock: @providerLock
						state: @providerState
						processOnlyLastMissedRun: true
			NEON
			));
		})->build();

	$providerContainer = $container->getService('scheduler.provider.container');
	Assert::type(ScheduleProviderContainer::class, $providerContainer);
	Assert::true($providerContainer->has('attributed'));
	Assert::true($providerContainer->has('default'));
	Assert::true($providerContainer->has('existing'));
	Assert::true($providerContainer->has('configured'));
	Assert::true($providerContainer->has('tagged'));
	Assert::type(AttributedScheduleProvider::class, $providerContainer->get('attributed'));
	Assert::type(DefaultScheduleProvider::class, $providerContainer->get('default'));
	Assert::type(DefaultScheduleProvider::class, $providerContainer->get('existing'));
	Assert::type(ScheduleProviderInterface::class, $providerContainer->get('configured'));
	Assert::type(DefaultScheduleProvider::class, $providerContainer->get('tagged'));
	Assert::notSame(AttributedDefaultScheduleProvider::class, $providerContainer->get('default')::class);
	Assert::notSame(AttributedTaggedScheduleProvider::class, $providerContainer->get('tagged')::class);

	$configuredSchedule = $providerContainer->get('configured')->getSchedule();
	Assert::type(TestLock::class, $configuredSchedule->getLock());
	Assert::type(ArrayAdapter::class, $configuredSchedule->getState());
	Assert::true($configuredSchedule->shouldProcessOnlyLastMissedRun());
	Assert::count(1, $configuredSchedule->getRecurringMessages());

	$transportFactory = $container->getService('scheduler.transportFactory');
	Assert::type(SchedulerTransportFactory::class, $transportFactory);
	Assert::type(SchedulerTransport::class, $container->getService('scheduler.transport.attributed'));
	Assert::type(SchedulerTransport::class, $container->getService('scheduler.transport.configured'));
	Assert::type(SchedulerTransport::class, $container->getService('scheduler.transport.default'));
	Assert::type(SchedulerTransport::class, $container->getService('scheduler.transport.existing'));
	Assert::type(SchedulerTransport::class, $container->getService('scheduler.transport.tagged'));

	$transport = $transportFactory->createTransport('schedule://default', [], new class implements SerializerInterface
	{

		/**
		 * @param array<string, mixed> $encodedEnvelope
		 */
		public function decode(array $encodedEnvelope): Envelope
		{
			throw new \LogicException('Not used.');
		}

		/**
		 * @return array<string, mixed>
		 */
		public function encode(Envelope $envelope): array
		{
			return [];
		}

	});

	Assert::type(SchedulerTransport::class, $transport);
	Assert::count(1, $transport->getMessageGenerator()->getSchedule()->getRecurringMessages());

	$attributedTransport = $transportFactory->createTransport('schedule://attributed', [], new class implements SerializerInterface
	{

		/**
		 * @param array<string, mixed> $encodedEnvelope
		 */
		public function decode(array $encodedEnvelope): Envelope
		{
			throw new \LogicException('Not used.');
		}

		/**
		 * @return array<string, mixed>
		 */
		public function encode(Envelope $envelope): array
		{
			return [];
		}

	});

	Assert::type(SchedulerTransport::class, $attributedTransport);
	Assert::count(1, $attributedTransport->getMessageGenerator()->getSchedule()->getRecurringMessages());
});
