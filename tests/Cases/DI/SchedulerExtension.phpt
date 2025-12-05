<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Scheduler\IScheduler;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Nette\DI\Extensions\InjectExtension;
use Tester\Assert;
use Tests\Fixtures\InjectableJob;

require_once __DIR__ . '/../../bootstrap.php';

// Custom parse case
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				callbackJob: Tests\Fixtures\CallbackJob
				scheduledJob: Tests\Fixtures\CustomJob

			scheduler:
				jobs:
					- {cron: '* * * * *', callback: Tests\Fixtures\CallbackJob::foo}
					- {cron: '* * * * *', callback: [@callbackJob, bar]}
					- Tests\Fixtures\CustomJob
					- @scheduledJob
			NEON
			));
		})->build();

	$scheduler = $container->getByType(IScheduler::class);
	Assert::type(IScheduler::class, $scheduler);
	Assert::count(4, $scheduler->getAll());
});

// Test job with class config
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			scheduler:
				jobs:
					myJob: {class: Tests\Fixtures\CustomJob}
			NEON
			));
		})->build();

	$scheduler = $container->getByType(IScheduler::class);
	Assert::type(IScheduler::class, $scheduler);
	Assert::count(1, $scheduler->getAll());
});

// Test job with inject: true
Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addExtension('inject', new InjectExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				dependency: Tests\Fixtures\SomeDependency

			scheduler:
				jobs:
					injectableJob: {class: Tests\Fixtures\InjectableJob, inject: true}
			NEON
			));
		})->build();

	$scheduler = $container->getByType(IScheduler::class);
	Assert::type(IScheduler::class, $scheduler);
	Assert::count(1, $scheduler->getAll());

	$jobs = $scheduler->getAll();
	$job = reset($jobs);
	Assert::type(InjectableJob::class, $job);
	Assert::notNull($job->getDependency());
	Assert::same('injected', $job->getDependency()->getValue());
});
