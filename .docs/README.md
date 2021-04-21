# Contributte Scheduler

Executing php callbacks using cron expression.

## Content

- [Setup](#setup)
- [Configuration](#configuration)
- [Jobs](#jobs)
- [Commands](#commands)

## Setup

Require package

```bash
composer require contributte/scheduler
```

Register extension

```neon
extensions:
	scheduler: Contributte\Scheduler\DI\SchedulerExtension
```

## Configuration

Set-up crontab. Use the `scheduler:run` command.

```
* * * * * php path-to-project/console scheduler:run
```

Optionally, you can set a temp path for storing lock files.

```neon
scheduler:
	path: '%tempDir%/scheduler'
```

## Jobs

This package defines 2 types of jobs:

- callback job
- service job

### Callback job

Register your callbacks under `scheduler.jobs` key.

```neon
services:
	stats: App\Model\Stats

scheduler:
	jobs:
		# stats must be registered as service and have method calculate
		- { cron: '* * * * *', callback: [ @stats, calculate ] }

		# monitor is class with static method echo
		- { cron: '*/2 * * * *', callback: App\Model\Monitor::echo }
```

Be careful with cron syntax, take a look at following example. You can also validate your cron
using [crontab.guru](https://crontab.guru).

```
	*	*	*	*	*
	-	-	-	-	-
	|	|	|	|	|
	|	|	|	|	|
	|	|	|	|	+----- day of week (0 - 7) (Sunday=0 or 7)
	|	|	|	+---------- month (1 - 12)
	|	|	+--------------- day of month (1 - 31)
	|	+-------------------- hour (0 - 23)
	+------------------------- min (0 - 59)
```

### Custom job

Create new class which implements `IJob` interface.

```php
use Contributte\Scheduler\IJob;

class ScheduledJob implements IJob
{

	private $dateService;

	private $statisticsService;

	public function __construct($dateService, $statisticsService) {
		$this->dateService = $dateService;
		$this->statisticsService = $statisticsService;
	}

	public function isDue(DateTime $dateTime): bool
	{
		if ($this->dateService->isRightTime($dateTime)) {
			return true;
		}
		return false;
	}

	public function run(): void
	{
		$this->statisticsService->calculate();
	}

}

```

Register your class into `config.neon` as regular services
into [nette dependency-injection container](https://doc.nette.org/en/3.0/dependency-injection).

```neon
scheduler:
	jobs:
		- App\Model\ScheduledJob()
		- App\Model\OtherScheduledJob()
```

You can also reference already registered service.

```neon
services:
	scheduledJob: App\Model\ScheduledJob

scheduler:
	jobs:
		- @scheduled
```

## Console

This package relies on `symfony/console`, use prepared [contributte/console](https://github.com/contributte/console)
integration.

```bash
composer require contributte/console
```

```neon
extensions:
	console: Contributte\Console\DI\ConsoleExtension(%consoleMode%)
```

After that you can fire one of these commands.

| Command		| Info					|
|----------------|--------------------	|
| scheduler:help | Print cron syntax.	|
| scheduler:list | List all jobs.		|
| scheduler:run  | Run all due jobs.	|
| scheduler:force-run  | Force run selected scheduler job.	|
