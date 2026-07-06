<?php declare(strict_types = 1);

namespace Contributte\Scheduler\DI\Pass;

use InvalidArgumentException;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\PhpGenerator\ClassType;
use stdClass;

abstract class AbstractPass
{

	protected CompilerExtension $extension;

	public function __construct(CompilerExtension $extension)
	{
		$this->extension = $extension;
	}

	public function loadPassConfiguration(): void
	{
		// Override in child.
	}

	public function beforePassCompile(): void
	{
		// Override in child.
	}

	public function afterPassCompile(ClassType $class): void
	{
		// Override in child.
	}

	protected function prefix(string $id): string
	{
		return $this->extension->prefix($id);
	}

	protected function getContainerBuilder(): ContainerBuilder
	{
		return $this->extension->getContainerBuilder();
	}

	protected function getConfig(): stdClass
	{
		/** @var stdClass $config */
		$config = (object) $this->extension->getConfig();

		return $config;
	}

	protected function validateScheduleName(string $name): void
	{
		if ($name === '' || preg_match('~^[A-Za-z0-9_.-]+$~D', $name) !== 1) {
			throw new InvalidArgumentException(sprintf('Schedule name "%s" contains unsupported characters.', $name));
		}
	}

	protected function getTransportName(string $name): string
	{
		return 'scheduler_' . $name;
	}

	/**
	 * @return array{provider: string|null, lock: string|null, state: string|null, processOnlyLastMissedRun: bool|null, shorthand: bool}
	 */
	protected function normalizeProviderConfig(mixed $config): array
	{
		if (is_string($config)) {
			return [
				'provider' => $config,
				'lock' => null,
				'state' => null,
				'processOnlyLastMissedRun' => null,
				'shorthand' => true,
			];
		}

		if (!$config instanceof stdClass) {
			throw new InvalidArgumentException('Provider configuration must be a string or structure.');
		}

		return [
			'provider' => is_string($config->provider ?? null) ? $config->provider : null,
			'lock' => is_string($config->lock ?? null) ? $config->lock : null,
			'state' => is_string($config->state ?? null) ? $config->state : null,
			'processOnlyLastMissedRun' => is_bool($config->processOnlyLastMissedRun ?? null) ? $config->processOnlyLastMissedRun : null,
			'shorthand' => false,
		];
	}

	protected function getServiceNameSuffix(string $name): string
	{
		if (!str_contains($name, '-')) {
			return $name;
		}

		return str_replace('-', '_', $name) . '__' . substr(md5($name), 0, 8);
	}

}
