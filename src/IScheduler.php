<?php

declare(strict_types = 1);

namespace Contributte\Scheduler;

interface IScheduler
{

	public function run(): void;

	public function add(IJob $job, ?string $key = NULL): void;

	public function get(string $key): ?IJob;

	/**
	 * @return IJob[]
	 */
	public function getAll(): array;

	public function remove(string $key): void;

	public function removeAll(): void;

}
