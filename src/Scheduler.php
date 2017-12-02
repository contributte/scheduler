<?php

namespace Tlapnet\Scheduler;

class Scheduler
{

	/** @var Job[] */
	private $jobs = [];

	/**
	 * @param Job $job
	 * @return void
	 */
	public function addJob(Job $job)
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
			$job->run();
		}
	}

}
