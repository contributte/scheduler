<?php declare(strict_types = 1);

namespace Contributte\Scheduler;

use Contributte\Scheduler\Helpers\Debugger;
use DateTime;
use Throwable;

class Scheduler implements IScheduler
{

	/** @var IJob[] */
	protected $jobs = [];

	public function run(): void
	{
		$dateTime = new DateTime();
		$jobs = $this->jobs;
		foreach ($jobs as $job) {
			if (!$job->isDue($dateTime)) {
				continue;
			}

			try {
				$job->run();
			} catch (Throwable $e) {
				Debugger::log($e);
			}
		}
	}

	/**
	 * @param string|int|null $key
	 */
	public function add(IJob $job, $key = null): void
	{
		if ($key !== null) {
			$this->jobs[$key] = $job;
			return;
		}

		$this->jobs[] = $job;
	}

	/**
	 * @param string|int $key
	 */
	public function get($key): ?IJob
	{
		return $this->jobs[$key] ?? null;
	}

	/**
	 * @return IJob[]
	 */
	public function getAll(): array
	{
		return $this->jobs;
	}

	/**
	 * @param string|int $key
	 */
	public function remove($key): void
	{
		unset($this->jobs[$key]);
	}

	public function removeAll(): void
	{
		$this->jobs = [];
	}

}
