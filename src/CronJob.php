<?php

namespace Tlapnet\Scheduler;

use Cron\CronExpression;
use DateTime;

abstract class CronJob implements IJob
{

	/** @var CronExpression */
	protected $expression;

	/**
	 * @param string $cron
	 */
	public function __construct($cron)
	{
		$this->expression = CronExpression::factory($cron);
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
	 * @return CronExpression
	 */
	public function getExpression()
	{
		return $this->expression;
	}

}
