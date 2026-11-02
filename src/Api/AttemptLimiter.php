<?php

namespace Sunnysideup\Ecommerce\Api;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

/**
 * A simple way to limit how many times something can be tried by one visitor,
 * e.g. checking if an email address already has an account.
 *
 * Attempts are counted per IP address (in the cache) AND per session.
 * Counting per session only would not be enough, because a visitor can simply start a new session.
 *
 * Usage:
 *
 *     $limiter = AttemptLimiter::create('RegistrationEmailTaken', 5, 3600);
 *     if ($limiter->isLimited()) { ... too many attempts ... }
 *     $limiter->record();
 */
class AttemptLimiter
{
    use Injectable;

    protected string $name;

    protected int $maxAttempts;

    protected int $seconds;

    /**
     * @param string $name        - what is being limited (e.g. RegistrationEmailTaken)
     * @param int    $maxAttempts - the number of attempts that are allowed in the time period
     * @param int    $seconds     - the time period
     */
    public function __construct(string $name, int $maxAttempts = 5, int $seconds = 3600)
    {
        $this->name = $name;
        $this->maxAttempts = $maxAttempts;
        $this->seconds = $seconds;
    }

    /**
     * Have there been too many attempts?
     */
    public function isLimited(): bool
    {
        if ($this->maxAttempts <= 0) {
            return false;
        }

        return $this->sessionCount() >= $this->maxAttempts || $this->ipCount() >= $this->maxAttempts;
    }

    /**
     * Record an attempt.
     */
    public function record(): void
    {
        // session
        $session = $this->getSession();
        if ($session) {
            $attempts = $this->sessionAttempts();
            $attempts[] = time();
            $session->set($this->sessionKey(), $attempts);
        }
        // ip address
        $cache = $this->getCache();
        if ($cache) {
            $key = $this->ipKey();
            $attempts = $this->ipAttempts();
            $attempts[] = time();
            try {
                $cache->set($key, $attempts, $this->seconds);
            } catch (\Throwable) {
                // if the cache is not available, then we still have the session.
            }
        }
    }

    protected function sessionCount(): int
    {
        return count($this->sessionAttempts());
    }

    protected function ipCount(): int
    {
        return count($this->ipAttempts());
    }

    /**
     * @return int[] timestamps of attempts in the time period
     */
    protected function sessionAttempts(): array
    {
        $session = $this->getSession();
        $attempts = $session ? $session->get($this->sessionKey()) : null;

        return $this->recent($attempts);
    }

    /**
     * @return int[] timestamps of attempts in the time period
     */
    protected function ipAttempts(): array
    {
        $cache = $this->getCache();
        if (! $cache) {
            return [];
        }
        try {
            $attempts = $cache->get($this->ipKey());
        } catch (\Throwable) {
            return [];
        }

        return $this->recent($attempts);
    }

    protected function recent($attempts): array
    {
        if (! is_array($attempts)) {
            return [];
        }
        $cutoff = time() - $this->seconds;

        return array_values(array_filter($attempts, static fn ($ts) => is_int($ts) && $ts > $cutoff));
    }

    protected function sessionKey(): string
    {
        return 'AttemptLimiter_' . $this->name;
    }

    protected function ipKey(): string
    {
        // cache keys can not contain special characters (e.g. : in IPv6 addresses)
        return 'AttemptLimiter_' . md5($this->name . '|' . $this->getIp());
    }

    protected function getIp(): string
    {
        $request = Controller::curr() instanceof Controller ? Controller::curr()->getRequest() : null;

        return (string) ($request?->getIP() ?? 'unknown');
    }

    protected function getSession()
    {
        $request = Controller::curr() instanceof Controller ? Controller::curr()->getRequest() : null;

        return $request?->getSession();
    }

    protected function getCache(): ?CacheInterface
    {
        try {
            return Injector::inst()->get(CacheInterface::class . '.Ecommerce');
        } catch (\Throwable) {
            return null;
        }
    }
}
