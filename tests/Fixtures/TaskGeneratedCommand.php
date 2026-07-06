<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Scheduler\Attribute\AsCronTask;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand('app:generated')]
#[AsPeriodicTask(frequency: 300, schedule: 'command-tasks', arguments: 'from-class')]
class TaskGeneratedCommand extends Command
{

	/** @var list<string> */
	public array $executions = [];

	#[AsCronTask(expression: '15 * * * *', schedule: 'command-tasks', arguments: ['value' => 'from-array'], transports: 'async')]
	public function scheduleOnMethod(): void
	{
		// Triggered via the command itself.
	}

	protected function configure(): void
	{
		$this->addArgument('value', InputArgument::OPTIONAL);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$value = $input->getArgument('value');

		if (is_string($value)) {
			$this->executions[] = $value;
		}

		return self::SUCCESS;
	}

}
