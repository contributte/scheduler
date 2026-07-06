<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Provider;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Scheduler\Messenger\ServiceCallMessage;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

class TaskScheduleProvider implements ScheduleProviderInterface
{

	/**
	 * @param list<array<string, mixed>> $tasks
	 */
	public function __construct(
		private ?ScheduleProviderInterface $provider,
		private array $tasks,
		private ?LockInterface $lock = null,
		private ?CacheInterface $state = null,
		private ?bool $processOnlyLastMissedRun = null,
	)
	{
	}

	public function getSchedule(): Schedule
	{
		$schedule = $this->provider !== null
			? clone $this->provider->getSchedule()
			: new Schedule();

		foreach ($this->tasks as $task) {
			$schedule->add($this->createRecurringMessage($task));
		}

		if ($this->lock !== null) {
			$schedule->lock($this->lock);
		}

		if ($this->state !== null) {
			$schedule->stateful($this->state);
		}

		if ($this->processOnlyLastMissedRun !== null) {
			$schedule->processOnlyLastMissedRun($this->processOnlyLastMissedRun);
		}

		return $schedule;
	}

	/**
	 * @param array<string, mixed> $task
	 */
	private function createRecurringMessage(array $task): RecurringMessage
	{
		$messageType = $task['messageType'] ?? 'service_call';

		if (!is_string($messageType)) {
			throw new \LogicException('Invalid task message definition.');
		}

		if ($messageType === 'run_command') {
			$command = $task['command'] ?? throw new \LogicException('Missing task command name.');
			$arguments = $task['arguments'] ?? null;

			if (!is_string($command) || (!is_string($arguments) && !is_array($arguments) && $arguments !== null)) {
				throw new \LogicException('Invalid command task definition.');
			}

			$input = $arguments === null
				? $command
				: (is_array($arguments)
					? (string) new ArrayInput(['command' => $command, ...$arguments])
					: $command . ($arguments !== '' ? ' ' . $arguments : ''));

			$message = new RunCommandMessage($input);
		} else {
			$service = $task['service'] ?? throw new \LogicException('Missing task service id.');
			$method = $task['method'] ?? throw new \LogicException('Missing task method.');
			$arguments = $task['arguments'] ?? [];

			if (!is_string($service) || !is_string($method) || !is_array($arguments)) {
				throw new \LogicException('Invalid task definition.');
			}

			$message = new ServiceCallMessage($service, $method, $arguments);
		}

		$transports = $task['transports'] ?? null;

		if ($transports !== null) {
			if (!is_string($transports) && !is_array($transports)) {
				throw new \LogicException('Invalid task transports definition.');
			}

			if (is_array($transports)) {
				$normalizedTransports = [];

				foreach ($transports as $transport) {
					if (!is_string($transport)) {
						throw new \LogicException('Invalid task transports definition.');
					}

					$normalizedTransports[] = $transport;
				}

				$transports = $normalizedTransports;
			}

			$message = new RedispatchMessage($message, $transports);
		}

		$trigger = $task['trigger'] ?? throw new \LogicException('Missing task trigger.');

		if ($trigger === 'cron') {
			$expression = $task['expression'] ?? throw new \LogicException('Missing cron expression.');

			if (!is_string($expression)) {
				throw new \LogicException('Invalid cron expression definition.');
			}

			$timezone = $task['timezone'] ?? null;
			if ($timezone !== null && !is_string($timezone) && !$timezone instanceof \DateTimeZone) {
				throw new \LogicException('Invalid cron timezone definition.');
			}

			$recurringMessage = RecurringMessage::cron($expression, $message, $timezone);
		} else {
			$frequency = $task['frequency'] ?? throw new \LogicException('Missing task frequency.');

			if (!is_string($frequency) && !is_int($frequency)) {
				throw new \LogicException('Invalid task frequency definition.');
			}

			$from = $task['from'] ?? null;
			$until = $task['until'] ?? null;

			if ($from !== null && !is_string($from)) {
				throw new \LogicException('Invalid task start definition.');
			}

			if ($until !== null && !is_string($until)) {
				throw new \LogicException('Invalid task end definition.');
			}

			$recurringMessage = $until !== null
				? RecurringMessage::every($frequency, $message, $from, $until)
				: RecurringMessage::every($frequency, $message, $from);
		}

		$jitter = $task['jitter'] ?? null;
		if ($jitter !== null) {
			if (!is_int($jitter)) {
				throw new \LogicException('Invalid task jitter definition.');
			}

			$recurringMessage = $recurringMessage->withJitter($jitter);
		}

		return $recurringMessage;
	}

}
