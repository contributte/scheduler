<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Command;

use Contributte\Scheduler\IScheduler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ForceRunCommand extends Command
{

	protected static string $defaultName = 'scheduler:force-run';

	private IScheduler $scheduler;

	public function __construct(IScheduler $scheduler)
	{
		parent::__construct();

		$this->scheduler = $scheduler;
	}

	protected function configure(): void
	{
		$this->setName(self::$defaultName)
			->setDescription('Force run selected scheduler job');
		$this->addArgument('key', InputArgument::REQUIRED, 'Job key');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$key = $input->getArgument('key');

		if (!is_string($key) && !is_int($key)) {
			return Command::FAILURE;
		}

		$job = $this->scheduler->get($key);

		if ($job === null) {
			return Command::FAILURE;
		}

		$job->run();

		return Command::SUCCESS;
	}

}
