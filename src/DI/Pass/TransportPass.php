<?php declare(strict_types = 1);

namespace Contributte\Scheduler\DI\Pass;

use Contributte\Scheduler\DI\SchedulerExtension;
use InvalidArgumentException;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Scheduler\Messenger\SchedulerTransportFactory;

class TransportPass extends AbstractPass
{

	public function loadPassConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('serializer'))
			->setFactory(PhpSerializer::class)
			->setAutowired(false);

		$builder->addDefinition($this->prefix('transportFactory'))
			->setFactory(SchedulerTransportFactory::class, ['@' . $this->prefix('provider.container')])
			->addTag(SchedulerExtension::TRANSPORT_FACTORY_TAG, 'scheduler')
			->setAutowired(false);
	}

	public function beforePassCompile(): void
	{
		foreach ($this->getContainerBuilder()->findByTag(SchedulerExtension::PROVIDER_TAG) as $serviceName => $tag) {
			$name = is_string($tag) ? $tag : null;

			if (!is_string($name) || $name === '') {
				throw new InvalidArgumentException(sprintf('Provider service %s must define a non-empty schedule name', $serviceName));
			}

			$this->getContainerBuilder()->addDefinition($this->prefix('transport.' . $this->getServiceNameSuffix($name)))
				->setFactory('@' . $this->prefix('transportFactory') . '::createTransport', [
					sprintf('schedule://%s', $name),
					['transport_name' => $this->getTransportName($name)],
					'@' . $this->prefix('serializer'),
				])
				->setAutowired(false)
				->addTag(SchedulerExtension::TRANSPORT_TAG, $this->getTransportName($name));
		}

		$this->validateUniqueTagValues(SchedulerExtension::TRANSPORT_FACTORY_TAG, 'Messenger transport factory');
		$this->validateUniqueTagValues(SchedulerExtension::TRANSPORT_TAG, 'Messenger transport');
	}

	private function validateUniqueTagValues(string $tag, string $label): void
	{
		$registered = [];

		foreach ($this->getContainerBuilder()->findByTag($tag) as $serviceName => $tagValue) {
			if (!is_string($tagValue) || $tagValue === '') {
				throw new InvalidArgumentException(sprintf('%s service %s must define a non-empty name.', $label, $serviceName));
			}

			if (isset($registered[$tagValue]) && $registered[$tagValue] !== $serviceName) {
				throw new InvalidArgumentException(sprintf('%s "%s" is defined by multiple services: %s, %s.', $label, $tagValue, $registered[$tagValue], $serviceName));
			}

			$registered[$tagValue] = $serviceName;
		}
	}

}
