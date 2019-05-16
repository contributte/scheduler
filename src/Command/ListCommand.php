<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Command;

use Contributte\Scheduler\CallbackJob;
use Contributte\Scheduler\ExpressionJob;
use Contributte\Scheduler\IJob;
use Contributte\Scheduler\IScheduler;
use Cron\CronExpression;
use Nette\Utils\DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Command
{

	/** @var string */
	protected static $defaultName = 'scheduler:list';

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
	 * @return string[]|callable[]|CronExpression[]
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
			$callback = $job->getCallback();
			if (is_string($callback)) {
				$row[] = $callback;
			} else {
				$class = $callback[0];
				$callback = $callback[1];
				$row[] = get_class($class) . '->' . $callback . '()';
			}
		} else {
			$row[] = 'Dynamic';
		}

		return $row;
	}

}
