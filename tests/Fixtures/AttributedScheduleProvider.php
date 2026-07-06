<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Scheduler\Attribute\AsSchedule;

#[AsSchedule('attributed')]
class AttributedScheduleProvider extends DefaultScheduleProvider
{

}
