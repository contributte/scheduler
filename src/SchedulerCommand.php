<?php

namespace Tlapnet\Scheduler;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SchedulerCommand extends Command
{

	/** @var Scheduler */
	private $scheduler;

	/**
	 * @param Scheduler $scheduler
	 */
	public function __construct(Scheduler $scheduler)
	{
		parent::__construct();
		$this->scheduler = $scheduler;
	}

	/**
	 * @return void
	 */
	protected function configure()
	{
		$this->setName('scheduler');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->scheduler->runJobs();
		return 0;
	}

}
