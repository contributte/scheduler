<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Scheduler\IScheduler;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Ninjify\Nunjuck\Toolkit;
use Tester\Assert;
use Tester\FileMock;

require_once __DIR__ . '/../../bootstrap.php';

// Custom parse case
Toolkit::test(function (): void {
	$loader = new ContainerLoader(TEMP_DIR, true);
	$class = $loader->load(function (Compiler $compiler): void {
		$compiler->addExtension('scheduler', new SchedulerExtension());
		$compiler->loadConfig(FileMock::create("
			services:
				callbackJob: Tests\Fixtures\CallbackJob
				scheduledJob: Tests\Fixtures\CustomJob

			scheduler:
				jobs:
					- {cron: '* * * * *', callback: Tests\Fixtures\CallbackJob::foo}
					- {cron: '* * * * *', callback: [@callbackJob, bar]}
					- Tests\Fixtures\CustomJob
					- @scheduledJob
			", 'neon'));
	}, [getmypid(), 1]);

	/** @var Container $container */
	$container = new $class();

	$scheduler = $container->getByType(IScheduler::class);
	Assert::type(IScheduler::class, $scheduler);
	Assert::count(4, $scheduler->getAll());
});
