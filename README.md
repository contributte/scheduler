# Scheduler

Small lib for executing php callbacks using cron expression.

## Installation

Install via composer.

```
composer require tlapnet/scheduler
```

Register extension.

```yaml
extensions:
	scheduler: Tlapnet\Scheduler\DI\SchedulerExtension
```

## Usage

Add some jobs.

```yaml
scheduler:
	jobs:
		- {cron: '* * * * *', callback: App\Model\Pirate::arrgghh}
		- {cron: '*/2 * * * *', callback: App\Model\Parrot::echo}
```

Don't forget setup crontab. Use `scheduler:run` command.

```
* * * * * php path-to-project/console scheduler:run
```