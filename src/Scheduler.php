<?php

namespace Tlapnet\Scheduler;

class Scheduler
{

	/** @var IJob[] */
	private $jobs = [];

	/**
	 * @param IJob $job
	 * @return void
	 */
	public function addJob(IJob $job)
	{
		$this->jobs[] = $job;
	}

	/**
	 * @return void
	 */
	public function runJobs()
	{
		$jobs = $this->jobs;
		foreach ($jobs as $job) {
			if (!$job->isDue())
				continue;
			$job->run();
		}
	}

}
