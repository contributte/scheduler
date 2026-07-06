<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Scheduler\Attribute\AsSchedule;

#[AsSchedule('tagged')]
class AttributedTaggedScheduleProvider extends DefaultScheduleProvider
{

}
