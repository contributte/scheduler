<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Scheduler\IScheduler;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Tester\Assert;

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
