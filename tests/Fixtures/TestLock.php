<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Symfony\Component\Lock\LockInterface;

class TestLock implements LockInterface
{

	public function acquire(bool $blocking = false): bool
	{
		return true;
	}

	public function refresh(?float $ttl = null): void
	{
		// noop
	}

	public function isAcquired(): bool
	{
		return true;
	}

	public function release(): void
	{
		// noop
	}

	public function getRemainingLifetime(): ?float
	{
		return null;
	}

	public function isExpired(): bool
	{
		return false;
	}

}
