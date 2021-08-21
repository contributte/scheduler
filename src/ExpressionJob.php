<?php declare(strict_types = 1);

namespace Contributte\Scheduler;

use Cron\CronExpression;
use DateTime;

abstract class ExpressionJob implements IJob
{

	/** @var CronExpression */
	protected $expression;

	public function __construct(string $cron)
	{
		$this->expression = new CronExpression($cron);
	}

	public function isDue(DateTime $dateTime, DateTimeInterface $lastRun = null): bool
	{
		return (
			$this->expression->isDue($dateTime)
			|| ($lastRun !== null && $lastRun < $this->expression->getPreviousRunDate($dateTime))
		);
	}

	public function getExpression(): CronExpression
	{
		return $this->expression;
	}

}
