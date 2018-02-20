<?php

declare(strict_types = 1);

namespace Contributte\Scheduler;

use Cron\CronExpression;
use DateTimeInterface;

abstract class ExpressionJob implements IJob
{

	/** @var CronExpression */
	protected $expression;

	public function __construct(string $cron)
	{
		$this->expression = CronExpression::factory($cron);
	}

	public function isDue(DateTimeInterface $dateTime): bool
	{
		return $this->expression->isDue($dateTime);
	}

	public function getExpression(): CronExpression
	{
		return $this->expression;
	}

}
