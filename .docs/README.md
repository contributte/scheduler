# Contributte Scheduler

Executing php callbacks using cron expression.

## Content

- [Setup](#setup)
- [Configuration](#configuration)
- [Jobs](#jobs)
- [Providers](#providers)
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

Experimental Symfony Scheduler provider integration is also available via `scheduler.providers`.
It is additive and does not replace the legacy `scheduler.jobs` setup.

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
		- App\Model\ScheduledJob
		- App\Model\OtherScheduledJob
```

You can also reference already registered service.

```neon
services:
	scheduledJob: App\Model\ScheduledJob

scheduler:
	jobs:
		- @scheduledJob
```

### Job with inject support

If your job class uses `inject*` methods for dependency injection, you can enable auto-injection using the `inject` option:

```neon
scheduler:
	jobs:
		myJob: {class: App\Model\ScheduledJob, inject: true}
```

This will automatically call all `inject*` methods on the job class after instantiation.

## Providers

You can explicitly register Symfony Scheduler providers by schedule name.
Each entry accepts either a provider class / `@service` shorthand or a structure
with `provider`, `lock`, `state`, and `processOnlyLastMissedRun` options.

```neon
services:
	defaultScheduleProvider: App\Scheduler\DefaultScheduleProvider

scheduler:
	providers:
		default: App\Scheduler\DefaultScheduleProvider
		reports: @defaultScheduleProvider
		generated:
			lock: @schedulerLock
			state: @schedulerState
			processOnlyLastMissedRun: true
```

`lock` must reference a service implementing `Symfony\Component\Lock\LockInterface`
and `state` must reference a service implementing `Symfony\Contracts\Cache\CacheInterface`.
Structure-only entries are useful for decorating discovered providers or task-generated schedules;
without a provider or tasks they simply describe an empty schedule.

Registered providers are exposed through an experimental `SchedulerTransportFactory`,
so `schedule://default` can be used where a Symfony Scheduler transport is needed.

Providers can also be discovered automatically from services implementing
`ScheduleProviderInterface` and using Symfony's `#[AsSchedule('name')]` attribute.
Explicit `scheduler.providers` entries and manually tagged
`contributte.scheduler.provider` services stay authoritative for the same schedule name.

Scheduler task attributes are also supported on services. Use Symfony's
`#[AsCronTask]` or `#[AsPeriodicTask]` on a class or public method and this extension
will generate or extend the target schedule provider additively.

If the service is a console command, task attributes generate `RunCommandMessage`
instances using the resolved command name instead of `ServiceCallMessage`.
Command-task execution therefore expects a `Symfony\Component\Console\Application`
service, typically provided by [contributte/console](https://github.com/contributte/console).

For each provider, this extension also registers a transport service named
`scheduler.transport.<name>`. These services are tagged for
[contributte/messenger](https://github.com/contributte/messenger), where they become available under
Messenger transport names like `scheduler_default`.

When [contributte/messenger](https://github.com/contributte/messenger) is present, the extension also wires Symfony's
`DispatchSchedulerEventListener` to messenger worker events. It prefers an existing application event dispatcher for
`PreRunEvent`, `PostRunEvent`, and `FailureEvent`; otherwise it falls back to its own lightweight
`scheduler.event.dispatcher` service.

If you use [contributte/console](https://github.com/contributte/console), the native Symfony
`debug:scheduler` command is also available for registered providers.

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
| debug:scheduler | List registered Symfony schedules. |
