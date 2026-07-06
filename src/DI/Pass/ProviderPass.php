<?php declare(strict_types = 1);

namespace Contributte\Scheduler\DI\Pass;

use Contributte\Scheduler\Container\ScheduleProviderContainer;
use Contributte\Scheduler\DI\SchedulerExtension;
use InvalidArgumentException;
use Nette\DI\Definitions\ServiceDefinition;
use ReflectionAttribute;
use ReflectionClass;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

class ProviderPass extends AbstractPass
{

	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('provider.container'))
			->setFactory(ScheduleProviderContainer::class)
			->setAutowired(false);

		foreach ($this->getConfig()->providers as $name => $provider) {
			$this->validateScheduleName($name);
			$providerConfig = $this->normalizeProviderConfig($provider);

			if ($providerConfig['provider'] === null) {
				continue;
			}

			$providerReference = $providerConfig['provider'];

			if (!str_starts_with($providerReference, '@')) {
				if (!class_exists($providerReference)) {
					throw new InvalidArgumentException(sprintf('Provider class scheduler > providers > %s was not found', $name));
				}

				if (!is_subclass_of($providerReference, ScheduleProviderInterface::class)) {
					throw new InvalidArgumentException(sprintf('Provider class scheduler > providers > %s must implement %s', $name, ScheduleProviderInterface::class));
				}
			}

			$builder->addDefinition($this->prefix('provider.' . $this->getServiceNameSuffix($name)))
				->setFactory($providerReference)
				->setAutowired(false)
				->addTag(SchedulerExtension::PROVIDER_TAG, $name);
		}
	}

	public function beforePassCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$manualProviders = [];

		foreach ($builder->findByTag(SchedulerExtension::PROVIDER_TAG) as $serviceName => $tag) {
			$name = is_string($tag) ? $tag : null;

			if (!is_string($name) || $name === '') {
				throw new InvalidArgumentException(sprintf('Provider service %s must define a non-empty schedule name', $serviceName));
			}

			$this->validateScheduleName($name);
			$manualProviders[$name] = $serviceName;
		}

		$this->registerAttributedProviders($manualProviders);

		$providers = [];

		foreach ($builder->findByTag(SchedulerExtension::PROVIDER_TAG) as $serviceName => $tag) {
			$name = is_string($tag) ? $tag : null;

			if (!is_string($name) || $name === '') {
				throw new InvalidArgumentException(sprintf('Provider service %s must define a non-empty schedule name', $serviceName));
			}

			$this->validateScheduleName($name);

			$definition = $builder->getDefinition($serviceName);
			if ($definition instanceof ServiceDefinition && $definition->getType() !== null && !is_a($definition->getType(), ScheduleProviderInterface::class, true)) {
				throw new InvalidArgumentException(sprintf('Provider service %s must implement %s', $serviceName, ScheduleProviderInterface::class));
			}

			if (isset($providers[$name]) && $providers[$name] !== $serviceName) {
				throw new InvalidArgumentException(sprintf('Schedule provider "%s" is defined by multiple services: %s, %s.', $name, $providers[$name], $serviceName));
			}

			$providers[$name] = $serviceName;
		}

		ksort($providers);

		/** @var ServiceDefinition $providerContainerDefinition */
		$providerContainerDefinition = $builder->getDefinition($this->prefix('provider.container'));
		$providerContainerDefinition->setArgument(0, $providers);
	}

	/**
	 * @param array<string, string> $manualProviders
	 */
	private function registerAttributedProviders(array $manualProviders): void
	{
		$builder = $this->getContainerBuilder();
		$attributedProviders = [];

		foreach ($builder->findByType(ScheduleProviderInterface::class) as $serviceName => $definition) {
			if ($definition->getTag(SchedulerExtension::PROVIDER_TAG) !== null) {
				continue;
			}

			$type = $definition->getType();

			if (!is_string($type) || !class_exists($type)) {
				continue;
			}

			$attribute = $this->getScheduleAttribute($type);

			if ($attribute === null) {
				continue;
			}

			$this->validateScheduleName($attribute->name);

			if (isset($manualProviders[$attribute->name])) {
				continue;
			}

			if (isset($attributedProviders[$attribute->name]) && $attributedProviders[$attribute->name] !== $serviceName) {
				throw new InvalidArgumentException(sprintf('Attribute-discovered schedule provider "%s" is defined by multiple services: %s, %s.', $attribute->name, $attributedProviders[$attribute->name], $serviceName));
			}

			$attributedProviders[$attribute->name] = $serviceName;
			$definition->addTag(SchedulerExtension::PROVIDER_TAG, $attribute->name);
		}
	}

	/**
	 * @param class-string $type
	 */
	private function getScheduleAttribute(string $type): ?AsSchedule
	{
		$reflection = new ReflectionClass($type);

		$attributes = $reflection->getAttributes(AsSchedule::class);

		if ($attributes === []) {
			return null;
		}

		/** @var ReflectionAttribute<AsSchedule> $attribute */
		$attribute = $attributes[0];

		return $attribute->newInstance();
	}

}
