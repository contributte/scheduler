<?php

namespace Tlapnet\Scheduler\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tlapnet\Scheduler\Scheduler;

class RunCommand extends Command
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
