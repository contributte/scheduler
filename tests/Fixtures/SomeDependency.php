<?php declare(strict_types = 1);

namespace Tests\Fixtures;

final class SomeDependency
{

	public function getValue(): string
	{
		return 'injected';
	}

}
