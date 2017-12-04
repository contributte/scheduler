<?php

namespace Tlapnet\Scheduler;

interface IJob
{

	/**
	 * @return bool
	 */
	public function isDue();

	/**
	 * @return void
	 */
	public function run();

}
