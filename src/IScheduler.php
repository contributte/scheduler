<?php

namespace Contributte\Scheduler;

interface IScheduler
{

	/**
	 * @return void
	 */
	public function run();

	/**
	 * @param IJob $job
	 * @param string|NULL $key
	 * @return void
	 */
	public function add(IJob $job, $key = NULL);

	/**
	 * @param string $key
	 * @return IJob|NULL
	 */
	public function get($key);

	/**
	 * @return IJob[]
	 */
	public function getAll();

	/**
	 * @param string $key
	 * @return void
	 */
	public function remove($key);

	/**
	 * @return void
	 */
	public function removeAll();

}
