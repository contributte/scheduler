<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Contributte\Scheduler\IJob;
use DateTime;

final class InjectableJob implements IJob
{

	private ?SomeDependency $dependency = null;

	public function injectDependency(SomeDependency $dependency): void
	{
		$this->dependency = $dependency;
	}

	public function getDependency(): ?SomeDependency
	{
		return $this->dependency;
	}

	public function isDue(DateTime $dateTime): bool
	{
		return true;
	}

	public function run(): void
	{
		// Nothing
	}

}
