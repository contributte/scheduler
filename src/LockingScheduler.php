<?php

namespace Tlapnet\Scheduler;

use DateTime;
use Tlapnet\Scheduler\Helpers\Debugger;

class LockingScheduler extends Scheduler
{

	/** @var string */
	protected $path;

	/**
	 * @param string $path
	 */
	public function __construct($path)
	{
		$this->path = $path;
	}

	/**
	 * @return void
	 */
	public function run()
	{
		if (!file_exists($this->path)) {
			mkdir($this->path, 0777, TRUE);
		}

		$dateTime = new DateTime();
		$jobs = $this->jobs;
		foreach ($jobs as $id => $job) {
			if (!$job->isDue($dateTime))
				continue;

			// Create lock
			$fp = fopen($this->path . '/' . $id . '.lock', 'w+');
			if (!flock($fp, LOCK_EX | LOCK_NB)) {  // acquire an exclusive lock
				fclose($fp);
				continue;
			}

			try {
				// Run job
				$job->run();
			} catch (\Exception $e) {
				Debugger::log($e);
			} finally {
				// Unlock
				flock($fp, LOCK_UN);
				unlink($this->path . '/' . $id . '.lock');
				fclose($fp);
			}
		}
	}

}
