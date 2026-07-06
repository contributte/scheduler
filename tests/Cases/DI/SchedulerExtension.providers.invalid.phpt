<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use InvalidArgumentException;
use Nette\DI\Compiler;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('scheduler', new SchedulerExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
					scheduler:
						providers:
							default: Tests\Fixtures\DefaultScheduleProvider

					services:
						conflictingTransport:
							class: stdClass
							tags:
								contributte.messenger.transport: scheduler_default
					NEON
					));
				})->build();
		},
		InvalidArgumentException::class,
		'Messenger transport "scheduler_default" is defined by multiple services: conflictingTransport, scheduler.transport.default.',
	);
});

Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('scheduler', new SchedulerExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
					scheduler:
						providers:
							default:
								provider: Tests\Fixtures\DefaultScheduleProvider
								lock: @missingLock
					NEON
					));
				})->build();
		},
		InvalidArgumentException::class,
		'Provider lock scheduler > providers > default > lock service "@missingLock" was not found',
	);
});

Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('scheduler', new SchedulerExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
					scheduler:
						providers:
							default:
								provider: Tests\Fixtures\DefaultScheduleProvider
								state: @missingState
					NEON
					));
				})->build();
		},
		InvalidArgumentException::class,
		'Provider state scheduler > providers > default > state service "@missingState" was not found',
	);
});

Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('scheduler', new SchedulerExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
					services:
						invalidLock: stdClass

					scheduler:
						providers:
							default:
								provider: Tests\Fixtures\DefaultScheduleProvider
								lock: @invalidLock
					NEON
					));
				})->build();
		},
		InvalidArgumentException::class,
		'Provider lock scheduler > providers > default > lock service "@invalidLock" must implement Symfony\Component\Lock\LockInterface',
	);
});

Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('scheduler', new SchedulerExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
					services:
						invalidState: stdClass

					scheduler:
						providers:
							default:
								provider: Tests\Fixtures\DefaultScheduleProvider
								state: @invalidState
					NEON
					));
				})->build();
		},
		InvalidArgumentException::class,
		'Provider state scheduler > providers > default > state service "@invalidState" must implement Symfony\Contracts\Cache\CacheInterface',
	);
});

Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('scheduler', new SchedulerExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
					scheduler:
						providers:
							default: Tests\Fixtures\DefaultScheduleProvider

					services:
						duplicateProvider:
							class: Tests\Fixtures\DefaultScheduleProvider
							tags:
								contributte.scheduler.provider: default
					NEON
					));
				})->build();
		},
		InvalidArgumentException::class,
		'Schedule provider "default" is defined by multiple services: scheduler.provider.default, duplicateProvider.',
	);
});

Toolkit::test(function (): void {
	Assert::exception(
		static function (): void {
			ContainerBuilder::of()
				->withCompiler(function (Compiler $compiler): void {
					$compiler->addExtension('scheduler', new SchedulerExtension());
					$compiler->addConfig(Neonkit::load(<<<'NEON'
					services:
						duplicateAttributedProvider: Tests\Fixtures\DuplicateAttributedScheduleProvider
						otherDuplicateAttributedProvider: Tests\Fixtures\OtherDuplicateAttributedScheduleProvider
					NEON
					));
				})->build();
		},
		InvalidArgumentException::class,
		'Attribute-discovered schedule provider "duplicate" is defined by multiple services: duplicateAttributedProvider, otherDuplicateAttributedProvider.',
	);
});
