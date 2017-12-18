<?php

namespace Contributte\Scheduler\Command;

use Contributte\Scheduler\IScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{

	/** @var IScheduler */
	private $scheduler;

	/**
	 * @param IScheduler $scheduler
	 */
	public function __construct(IScheduler $scheduler)
	{
		parent::__construct();
		$this->scheduler = $scheduler;
	}

	/**
	 * @return void
	 */
	protected function configure()
	{
		$this->setName('scheduler:run')
			->setDescription('Run scheduler jobs');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->scheduler->run();
		return 0;
	}

}
