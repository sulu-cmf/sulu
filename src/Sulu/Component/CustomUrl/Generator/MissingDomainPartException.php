<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Generator;

use Sulu\Component\Rest\Exception\RestException;

/**
 * Thrown when a missing domain-part is detected.
 */
class MissingDomainPartException extends RestException
{
    /**
     * @var array<string>
     */
    public function __construct(
        private string $baseDomain,
        private array $domainParts,
        private string $domain,
    ) {
        parent::__construct(
            \sprintf('Missing domain-part for base-domain "%s" detected. Result domain: "%s"', $baseDomain, $domain),
            9003
        );
    }

    public function getBaseDomain(): string
    {
        return $this->baseDomain;
    }

    /**
     * @return array<string>
     */
    public function getDomainParts(): array
    {
        return $this->domainParts;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
