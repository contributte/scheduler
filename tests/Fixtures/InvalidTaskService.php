<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Scheduler\Attribute\AsCronTask;

#[AsCronTask(expression: '* * * * *', schedule: 'broken', method: 'missing')]
class InvalidTaskService
{

}
