<?php declare(strict_types = 1);

namespace Contributte\Scheduler;

interface IScheduler
{

	public function run(): void;

	/**
	 * @param string|int|null $key
	 */
	public function add(IJob $job, $key = null): void;

	/**
	 * @param string|int $key
	 */
	public function get($key): ?IJob;

	/**
	 * @return IJob[]
	 */
	public function getAll(): array;

	/**
	 * @param string|int $key
	 */
	public function remove($key): void;

	public function removeAll(): void;

}
