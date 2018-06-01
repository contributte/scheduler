<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Command;

use Contributte\Scheduler\CallbackJob;
use Contributte\Scheduler\ExpressionJob;
use Contributte\Scheduler\IJob;
use Contributte\Scheduler\IScheduler;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{

	/** @var IScheduler */
	private $scheduler;

	public function __construct(IScheduler $scheduler)
	{
		parent::__construct();
		$this->scheduler = $scheduler;
	}

	protected function configure(): void
	{
		$this->setName('scheduler:list')
			->setDescription('List all scheduler jobs');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$jobs = $this->scheduler->getAll();
		$table = new Table($output);
		$table->setHeaders(['Key', 'Type', 'Is due', 'Cron', 'Callback']);
		$dateTime = new DateTime();
		foreach ($jobs as $key => $job) {
			$table->addRow(self::formatRow(is_string($key) ? $key : '', $job, $dateTime));
		}
		$table->render();
		return 0;
	}

	/**
	 * @return string[]
	 */
	private static function formatRow(string $key, IJob $job, DateTime $dateTime): array
	{
		// Common
		$row = [
			$key,
			get_class($job),
			$job->isDue($dateTime) ? 'TRUE' : 'FALSE',
		];
		// Expression
		if ($job instanceof ExpressionJob) {
			$row[] = $job->getExpression();
		} else {
			$row[] = 'Dynamic';
		}
		// Callback
		if ($job instanceof CallbackJob) {
			$row[] = $job->getCallback();
		} else {
			$row[] = 'Dynamic';
		}
		return $row;
	}

}
