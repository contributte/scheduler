<?php

namespace Tlapnet\Scheduler;

use Cron\CronExpression;

class Job
{

	/** @var string */
	private $cron;

	/** @var callable */
	private $callback;

	/**
	 * @param string $cron
	 * @param callable $callback
	 */
	public function __construct($cron, $callback)
	{
		$this->cron = $cron;
		$this->callback = $callback;
	}

	/**
	 * @return bool
	 */
	public function isDue()
	{
		$cron = CronExpression::factory($this->cron);
		return $cron->isDue();
	}

	/**
	 * @return void
	 */
	public function run()
	{
		if (!$this->isDue()) {
			return;
		}
		call_user_func($this->callback);
	}

}
