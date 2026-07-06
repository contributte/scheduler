<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

class DefaultScheduleProvider implements ScheduleProviderInterface
{

	public function getSchedule(): Schedule
	{
		return (new Schedule())
			->add(RecurringMessage::every(3600, new \stdClass()));
	}

}
