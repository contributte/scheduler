<?php declare(strict_types = 1);

namespace Tests\Cases\DI;

use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Nette\DI\Compiler;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Scheduler\Command\DebugCommand;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->addConfig(Neonkit::load(<<<'NEON'
			services:
				defaultScheduleProvider: Tests\Fixtures\DefaultScheduleProvider
				attributedScheduleProvider: Tests\Fixtures\AttributedScheduleProvider

			scheduler:
				providers:
					default: @defaultScheduleProvider
			NEON
			));
		})->build();

	$command = $container->getService('scheduler.debugCommand');
	Assert::type(DebugCommand::class, $command);

	$tester = new CommandTester($command);
	$tester->execute([]);

	$output = $tester->getDisplay(true);
	Assert::contains('Scheduler', $output);
	Assert::contains('attributed', $output);
	Assert::contains('default', $output);
	Assert::contains('Trigger', $output);
	Assert::same(0, $tester->getStatusCode());
});

Toolkit::test(function (): void {
	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('scheduler', new SchedulerExtension());
		})->build();

	$command = $container->getService('scheduler.debugCommand');
	Assert::type(DebugCommand::class, $command);

	$tester = new CommandTester($command);
	$tester->execute([]);

	Assert::contains('No schedules found.', $tester->getDisplay(true));
	Assert::same(2, $tester->getStatusCode());
});
