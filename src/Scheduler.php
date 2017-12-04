<?php

namespace Tlapnet\Scheduler;

class Scheduler
{

	/** @var IJob[] */
	private $jobs = [];

	/**
	 * @param IJob $job
	 * @param string|NULL $key
	 * @return void
	 */
	public function add(IJob $job, $key = NULL)
	{
		if ($key !== NULL) {
			$this->jobs[$key] = $job;
			return;
		}
		$this->jobs[] = $job;
	}

	/**
	 * @param string $key
	 * @return void
	 */
	public function remove($key)
	{
		unset($this->jobs[$key]);
	}

	/**
	 * @return void
	 */
	public function run()
	{
		$jobs = $this->jobs;
		foreach ($jobs as $job) {
			if (!$job->isDue())
				continue;
			$job->run();
		}
	}

	/**
	 * @return IJob[]
	 */
	public function getAll()
	{
		return $this->jobs;
	}

}
