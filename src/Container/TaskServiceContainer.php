<?php declare(strict_types = 1);

namespace Contributte\Scheduler\Container;

use Contributte\Scheduler\Exceptions\LogicalException;
use Nette\DI\Container;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @implements ServiceProviderInterface<object>
 */
class TaskServiceContainer implements ContainerInterface, ServiceProviderInterface
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

	public function get(string $id): object
	{
		if (!$this->has($id)) {
			throw new LogicalException(sprintf('Task service "%s" is not defined.', $id));
		}

		$service = $this->container->getService($this->map[$id]);
		if (!is_object($service)) {
			throw new LogicalException(sprintf('Task service "%s" must resolve to an object.', $id));
		}

		return $service;
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
		return array_fill_keys(array_keys($this->map), 'object');
	}

}
