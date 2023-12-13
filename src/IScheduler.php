<?php declare(strict_types = 1);

namespace Contributte\Scheduler;

interface IScheduler
{

	public function run(): void;

	public function add(IJob $job, string|int|null $key = null): void;

	public function get(string|int $key): ?IJob;

	/**
	 * @return IJob[]
	 */
	public function getAll(): array;

	public function remove(string|int $key): void;

	public function removeAll(): void;

}
