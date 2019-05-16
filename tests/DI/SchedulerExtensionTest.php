<?php declare(strict_types = 1);

namespace Tests\Contributte\Scheduler\DI;

use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Scheduler\IScheduler;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tests\Contributte\Scheduler\MockeryTest;

final class SchedulerExtensionTest extends MockeryTest
{

	public function testRegister(): void
	{
		$loader = new ContainerLoader(__DIR__ . '/temp', true);
		$class = $loader->load(function (Compiler $compiler): void {
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => '',
				],
			]);
			$compiler->addExtension('scheduler', new SchedulerExtension());
			$compiler->loadConfig(__DIR__ . '/jobs.config.neon');
		});
		/** @var Container $container */
		$container = new $class();

		$scheduler = $container->getByType(IScheduler::class);
		self::assertInstanceOf(IScheduler::class, $scheduler);

		self::assertCount(3, $scheduler->getAll());
	}

}
