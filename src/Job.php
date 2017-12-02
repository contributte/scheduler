<?php

namespace Tlapnet\Scheduler;

use Cron\CronExpression;
use DateTime;

class Job
{

	/** @var CronExpression */
	private $expression;

	/** @var callable */
	private $callback;

	/**
	 * @param string $cron
	 * @param callable $callback
	 */
	public function __construct($cron, $callback)
	{
		$this->expression = CronExpression::factory($cron);
		$this->callback = $callback;
	}

	/**
	 * @return bool
	 */
	public function isDue()
	{
		return $this->expression->isDue();
	}

	/**
	 * @param DateTime $dateTime
	 * @return bool
	 */
	public function isDueByDate(DateTime $dateTime)
	{
		return $this->expression->isDue($dateTime);
	}

	/**
	 * @return void
	 */
	public function run()
	{
		if (!$this->isDue())
			return;
		call_user_func($this->callback);
	}

}
