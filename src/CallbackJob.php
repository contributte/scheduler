<?php declare(strict_types = 1);

namespace Contributte\Scheduler;

class CallbackJob extends ExpressionJob
{

	/** @var callable */
	private $callback;

	public function __construct(string $cron, callable $callback)
	{
		parent::__construct($cron);
		$this->callback = $callback;
	}

	public function run(): void
	{
		call_user_func($this->callback);
	}

	public function getCallback(): callable
	{
		return $this->callback;
	}

}
