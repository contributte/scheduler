<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HelpCommand extends Command
{

	/** @var string */
	protected static $defaultName = 'scheduler:help';

	protected function configure(): void
	{
		$this->setName(self::$defaultName)
			->setDescription('Print cron syntax');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('Cron syntax: ');
		$output->writeln('
*    *    *    *    *
-    -    -    -    -
|    |    |    |    |
|    |    |    |    |
|    |    |    |    +----- day of week (0 - 7) (Sunday=0 or 7)
|    |    |    +---------- month (1 - 12)
|    |    +--------------- day of month (1 - 31)
|    +-------------------- hour (0 - 23)
+------------------------- min (0 - 59)');
		$output->writeln('');

		return 0;
	}

}
