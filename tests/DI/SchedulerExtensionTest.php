<?php

namespace Tests\Tlapnet\Scheduler;

use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tlapnet\Scheduler\DI\SchedulerExtension;
use Tlapnet\Scheduler\IScheduler;

final class SchedulerExtensionTest extends MockeryTest
{

	/**
	 * @return void
	 */
	public function testRegister()
	{
		$loader = new ContainerLoader(__DIR__ . '/temp', TRUE);
		$class = $loader->load(function (Compiler $compiler) {
			$compiler->addConfig([
				'parameters' => [
					'tempDir' => '',
				],
			]);
			$compiler->addExtension('scheduler', new SchedulerExtension());
		});
		/** @var Container $container */
		$container = new $class;
		self::assertInstanceOf(IScheduler::class, $container->getByType(IScheduler::class));
	}

}
