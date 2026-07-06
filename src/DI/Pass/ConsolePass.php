<?php declare(strict_types = 1);

namespace Contributte\Scheduler\DI\Pass;

use Symfony\Component\Scheduler\Command\DebugCommand;

class ConsolePass extends AbstractPass
{

	public function loadPassConfiguration(): void
	{
		$this->getContainerBuilder()->addDefinition($this->prefix('debugCommand'))
			->setFactory(DebugCommand::class, ['@' . $this->prefix('provider.container')])
			->setAutowired(false);
	}

}
