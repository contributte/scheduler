<?php declare(strict_types = 1);

namespace Tests\Contributte\Scheduler;

use Mockery;
use PHPUnit\Framework\TestCase;

abstract class MockeryTest extends TestCase
{

	protected function tearDown(): void
	{
		Mockery::close();
	}

}
