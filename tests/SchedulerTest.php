<?php

namespace Tests\Tlapnet\Scheduler;

use Mockery;
use Mockery\MockInterface;
use Tlapnet\Scheduler\IJob;
use Tlapnet\Scheduler\Scheduler;

final class SchedulerTest extends MockeryTest
{

	/**
	 * @return void
	 */
	public function testRun()
	{
		/** @var MockInterface|IJob $pendingJob */
		$pendingJob = Mockery::mock(IJob::class)
			->shouldReceive('isDue')
			->andReturn(FALSE)
			->once()
			->getMock()
			->shouldNotReceive('run')
			->getMock();

		/** @var MockInterface|IJob $readyJob */
		$readyJob = Mockery::mock(IJob::class)
			->shouldReceive('isDue')
			->andReturn(TRUE)
			->once()
			->getMock()
			->shouldReceive('run')
			->once()
			->getMock();

		$scheduler = new Scheduler();
		$scheduler->add($pendingJob);
		$scheduler->add($readyJob);

		// Execute
		$scheduler->run();
	}

	/**
	 * @return void
	 */
	public function testManage()
	{
		/** @var MockInterface|IJob $foo */
		$foo = Mockery::mock(IJob::class);

		/** @var MockInterface|IJob $bar */
		$bar = Mockery::mock(IJob::class);

		// Create empty
		$scheduler = new Scheduler();
		self::assertEmpty($scheduler->getAll());

		// Add
		$scheduler->add($foo);
		$scheduler->add($bar, 'key');
		self::assertEquals([$foo, 'key' => $bar], $scheduler->getAll());

		// Get
		self::assertSame($foo, $scheduler->get(0));
		self::assertSame($bar, $scheduler->get('key'));

		// Remove
		$scheduler->remove('key');
		self::assertNull($scheduler->get('key'));
		self::assertEquals([$foo], $scheduler->getAll());

		// Remove all
		$scheduler->removeAll();
		self::assertEmpty($scheduler->getAll());
	}

}
