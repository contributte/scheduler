<?php declare(strict_types = 1);

namespace Contributte\Scheduler;

use Contributte\Scheduler\Helpers\Debugger;
use DateTime;
use RuntimeException;
use Throwable;

class LockingScheduler extends Scheduler
{

	/** @var string */
	protected $path;

	public function __construct(string $path)
	{
		$this->path = $path;
	}

	public function run(): void
	{
		if (!file_exists($this->path) && !mkdir($this->path, 0777, true) && !is_dir($this->path)) {
			throw new RuntimeException(sprintf('Directory `%s` was not created', $this->path));
		}

		$dateTime = new DateTime();
		$jobs = $this->jobs;

		foreach ($jobs as $id => $job) {
			if (!$job->isDue($dateTime)) {
				continue;
			}

			// Create lock
			$fp = fopen($this->path . '/' . $id . '.lock', 'w+');
			if (!flock($fp, LOCK_EX | LOCK_NB)) {  // acquire an exclusive lock
				fclose($fp);
				continue;
			}

			try {
				// Run job
				$job->run();
			} catch (Throwable $e) {
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
