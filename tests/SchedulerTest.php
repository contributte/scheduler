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

}
