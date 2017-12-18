<?php

namespace Contributte\Scheduler;

use DateTime;

interface IJob
{

	/**
	 * @param DateTime $dateTime
	 * @return bool
	 */
	public function isDue(DateTime $dateTime);

	/**
	 * @return void
	 */
	public function run();

}
