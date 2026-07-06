<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Container;

use Contributte\Scheduler\Exceptions\LogicalException;
use Nette\DI\Container;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @implements ServiceProviderInterface<ScheduleProviderInterface>
 */
class ScheduleProviderContainer implements ServiceProviderInterface
{

	/** @var array<string, string> */
	private array $map;

	private Container $container;

	/**
	 * @param array<string, string> $map
	 */
	public function __construct(array $map, Container $container)
	{
		$this->map = $map;
		$this->container = $container;
	}

	public function get(string $id): ScheduleProviderInterface
	{
		if (!$this->has($id)) {
			throw new LogicalException(sprintf('Schedule provider "%s" is not defined.', $id));
		}

		$provider = $this->container->getService($this->map[$id]);
		if (!$provider instanceof ScheduleProviderInterface) {
			throw new LogicalException(sprintf('Schedule provider "%s" must implement %s.', $id, ScheduleProviderInterface::class));
		}

		return $provider;
	}

	public function has(string $id): bool
	{
		return isset($this->map[$id]);
	}

	/**
	 * @return array<string, string>
	 */
	public function getProvidedServices(): array
	{
		return array_fill_keys(array_keys($this->map), ScheduleProviderInterface::class);
	}

}
