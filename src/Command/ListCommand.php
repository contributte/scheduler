<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Command;

use Contributte\Scheduler\CallbackJob;
use Contributte\Scheduler\Exceptions\LogicalException;
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

	protected static string $defaultName = 'scheduler:list';

	private IScheduler $scheduler;

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

		return Command::SUCCESS;
	}

	/**
	 * @return string[]|callable[]|CronExpression[]
	 */
	private static function formatRow(string $key, IJob $job, DateTime $dateTime): array
	{
		// Common
		$row = [
			$key,
			$job::class,
			$job->isDue($dateTime) ? 'TRUE' : 'FALSE',
		];

		// Expression
		$row[] = $job instanceof ExpressionJob ? $job->getExpression() : 'Dynamic';

		// Callback
		if ($job instanceof CallbackJob) {
			$callback = $job->getCallback();
			if (is_string($callback)) {
				$row[] = $callback;
			} elseif (is_array($callback)) {
				$class = $callback[0];
				$callback = $callback[1];
				$row[] = $class::class . '->' . $callback . '()';
			} else {
				throw new LogicalException('Unknown callback');
			}
		} else {
			$row[] = 'Dynamic';
		}

		return $row;
	}

}
