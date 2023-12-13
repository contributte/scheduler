<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Command;

use Contributte\Scheduler\IScheduler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'scheduler:run',
	description: 'Run scheduler jobs'
)]
class RunCommand extends Command
{

	private IScheduler $scheduler;

	public function __construct(IScheduler $scheduler)
	{
		parent::__construct();

		$this->scheduler = $scheduler;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->scheduler->run();

		return Command::SUCCESS;
	}

}
