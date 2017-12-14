<?php

namespace Tlapnet\Scheduler;

class CallbackJob extends ExpressionJob
{

	/** @var callable */
	private $callback;

	/**
	 * @param string $cron
	 * @param callable $callback
	 */
	public function __construct($cron, $callback)
	{
		parent::__construct($cron);
		$this->callback = $callback;
	}

	/**
	 * @return void
	 */
	public function run()
	{
		call_user_func($this->callback);
	}

	/**
	 * @return callable
	 */
	public function getCallback()
	{
		return $this->callback;
	}

}
