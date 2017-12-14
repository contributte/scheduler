<?php

namespace Tlapnet\Scheduler;

use DateTime;
use Tlapnet\Scheduler\Helpers\Debugger;

class Scheduler implements IScheduler
{

	/** @var IJob[] */
	protected $jobs = [];

	/**
	 * @return void
	 */
	public function run()
	{
		$dateTime = new DateTime();
		$jobs = $this->jobs;
		foreach ($jobs as $job) {
			if (!$job->isDue($dateTime))
				continue;
			try {
				$job->run();
			} catch (\Exception $e) {
				Debugger::log($e);
			}
		}
	}

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
	 * @return IJob|NULL
	 */
	public function get($key)
	{
		return isset($this->jobs[$key]) ? $this->jobs[$key] : NULL;
	}

	/**
	 * @return IJob[]
	 */
	public function getAll()
	{
		return $this->jobs;
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
	public function removeAll()
	{
		$this->jobs = [];
	}

}
