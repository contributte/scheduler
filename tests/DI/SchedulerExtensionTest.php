<?php declare(strict_types = 1);

namespace Tests\Contributte\Scheduler;

use Contributte\Scheduler\DI\SchedulerExtension;
use Contributte\Scheduler\IScheduler;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;

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
		});
		/** @var Container $container */
		$container = new $class();
		self::assertInstanceOf(IScheduler::class, $container->getByType(IScheduler::class));
	}

}
