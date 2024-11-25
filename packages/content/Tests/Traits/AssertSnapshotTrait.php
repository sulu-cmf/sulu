<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Traits;

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Symfony\Component\HttpFoundation\Response;

trait AssertSnapshotTrait
{
    use PHPMatcherAssertions;

    /**
     * @param object $actualResponse
     */
    protected function assertResponseSnapshot(
        string $snapshotPatternFilename,
        $actualResponse,
        int $statusCode = 200,
        string $message = ''
    ): void {
        $this->assertInstanceOf(Response::class, $actualResponse);
        $responseContent = $actualResponse->getContent();
        $this->assertHttpStatusCode($statusCode, $actualResponse);
        $this->assertIsString($responseContent);

        $this->assertSnapshot($snapshotPatternFilename, $responseContent, $message);
    }

    /**
     * @param mixed[] $array
     */
    protected function assertArraySnapshot(
        string $snapshotPatternFilename,
        array $array,
        string $message = ''
    ): void {
        $arrayContent = \json_encode($array);
        $this->assertIsString($arrayContent);

        $this->assertSnapshot($snapshotPatternFilename, $arrayContent, $message);
    }

    protected function assertSnapshot(
        string $snapshotPatternFilename,
        string $content,
        string $message = ''
    ): void {
        $snapshotFolder = $this->getCalledClassFolder() . \DIRECTORY_SEPARATOR . $this->getSnapshotFolder();
        $snapshotPattern = \file_get_contents($snapshotFolder . \DIRECTORY_SEPARATOR . $snapshotPatternFilename);
        $this->assertIsString($snapshotPattern);

        $this->assertMatchesPattern(\trim($snapshotPattern), \trim($content), $message);
    }

    private function getCalledClassFolder(): string
    {
        $calledClass = static::class;

        /** @var string $fileName */
        $fileName = (new \ReflectionClass($calledClass))->getFileName();

        return \dirname($fileName);
    }

    protected function getSnapshotFolder(): string
    {
        return 'snapshots';
    }
}
