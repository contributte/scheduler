<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Command;

use Contributte\Scheduler\IScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends Command
{

	/** @var string */
	protected static $defaultName = 'scheduler:run';

	/** @var IScheduler */
	private $scheduler;

	public function __construct(IScheduler $scheduler)
	{
		parent::__construct();
		$this->scheduler = $scheduler;
	}

	protected function configure(): void
	{
		$this->setName(self::$defaultName)
			->setDescription('Run scheduler jobs');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->scheduler->run();

		return 0;
	}

}
