<?php

namespace Tlapnet\Scheduler\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tlapnet\Scheduler\CallbackJob;
use Tlapnet\Scheduler\IJob;
use Tlapnet\Scheduler\Scheduler;

class ListCommand extends Command
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
		$this->setName('scheduler:list')
			->setDescription('List all scheduler jobs')
			->addOption('due', NULL, InputOption::VALUE_OPTIONAL);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Due Option
		$dueOption = $input->getOption('due');
		if ($dueOption !== NULL) {
			$dueOption = (bool) $dueOption;
		}
		$jobs = $this->scheduler->getAll();
		$table = new Table($output);
		$table->setHeaders(['Key', 'Type', 'Is due', 'Cron', 'Callback']);
		foreach ($jobs as $key => $job) {
			// Skip due option
			if ($dueOption !== NULL && $dueOption !== $job->isDue()) {
				continue;
			}
			$table->addRow(self::formatRow($key, $job));
		}
		$table->render();
		return 0;
	}

	/**
	 * @param string $key
	 * @param IJob $job
	 * @return string[]
	 */
	private static function formatRow($key, IJob $job)
	{
		// Common
		$row = [
			$key,
			get_class($job),
			$job->isDue() ? 'TRUE' : 'FALSE',
		];
		// CallbackJob
		if ($job instanceof CallbackJob) {
			$row[] = $job->getExpression();
			$row[] = $job->getCallback();
		}
		return $row;
	}

}
