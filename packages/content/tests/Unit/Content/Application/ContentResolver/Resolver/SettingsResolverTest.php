<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Unit\Content\Application\ContentResolver\Resolver;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\TestBundle\Testing\SetGetPrivatePropertyTrait;
use Sulu\Component\Localization\Localization;
use Sulu\Component\Localization\Manager\LocalizationManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Sulu\Content\Application\ContentResolver\Resolver\SettingsResolver;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;

/**
 * @phpstan-import-type SettingsData from SettingsResolver
 */
class SettingsResolverTest extends TestCase
{
    use ProphecyTrait;
    use SetGetPrivatePropertyTrait;

    private SettingsResolver $resolver;

    /** @var ObjectProphecy<LocalizationManagerInterface> */
    private ObjectProphecy $localizationManager;

    /** @var ObjectProphecy<WebspaceManagerInterface> */
    private ObjectProphecy $webspaceManager;

    protected function setUp(): void
    {
        $this->localizationManager = $this->prophesize(LocalizationManagerInterface::class);
        $this->webspaceManager = $this->prophesize(WebspaceManagerInterface::class);

        $this->resolver = new SettingsResolver(
            $this->webspaceManager->reveal(),
            $this->localizationManager->reveal()
        );
    }

    public function testResolveAvailableLocales(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->addAvailableLocale('de');
        $exampleDimension->addAvailableLocale('en');

        $result = $this->resolver->resolve($exampleDimension);
        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame(['de', 'en'], $content['availableLocales']);
    }

    public function testResolveNoAvailableLocales(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);

        $result = $this->resolver->resolve($exampleDimension);
        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame([], $content['availableLocales']);
    }

    public function testResolveLocalizationsNoUrl(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);

        $result = $this->resolver->resolve($exampleDimension);
        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame([], $content['localizations']);
    }

    public function testResolveLocalizations(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setTemplateData(['url' => '/test']);
        $exampleDimension->setMainWebspace('sulu_io');
        $exampleDimension->addAvailableLocale('de');
        $exampleDimension->addAvailableLocale('en');

        $this->localizationManager->getLocalizations()->willReturn([
            'de' => new Localization('de', 'DE'),
            'en' => new Localization('en', 'US'),
            'fr' => new Localization('fr', 'FR'),
        ])->shouldBeCalled();

        $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            null,
            'de',
            'sulu_io',
        )->willReturn('/de/test')->shouldBeCalled();

        $this->webspaceManager->findUrlByResourceLocator(
            '/test',
            null,
            'en',
            'sulu_io',
        )->willReturn('/en/test')->shouldBeCalled();

        $this->webspaceManager->findUrlByResourceLocator(
            '/',
            null,
            'fr',
            'sulu_io',
        )->willReturn('/fr/')->shouldBeCalled();

        $result = $this->resolver->resolve($exampleDimension);
        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame([
            'de' => [
                'locale' => 'de',
                'url' => '/de/test',
                'country' => 'DE',
                'alternate' => true,
            ],
            'en' => [
                'locale' => 'en',
                'url' => '/en/test',
                'country' => 'US',
                'alternate' => true,
            ],
            'fr' => [
                'locale' => 'fr',
                'url' => '/fr/',
                'country' => 'FR',
                'alternate' => false,
            ],
        ], $content['localizations']);
    }

    public function testResolveWebspace(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setMainWebspace('sulu_io');

        $result = $this->resolver->resolve($exampleDimension);
        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame('sulu_io', $content['mainWebspace']);
    }

    public function testResolveTemplateData(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setTemplateKey('default');
        $exampleDimension->setTemplateData(['exampleKey' => 'exampleValue']);

        $result = $this->resolver->resolve($exampleDimension);
        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame('default', $content['template']);
    }

    public function testResolveAuthorData(): void
    {
        $author = new Contact();
        $this->setPrivateProperty($author, 'id', 1);
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setAuthored(new \DateTimeImmutable('2021-01-01'));
        $exampleDimension->setAuthor($author);

        $result = $this->resolver->resolve($exampleDimension);
        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame(1, $content['author']);
        self::assertSame('2021-01-01', $content['authored']?->format('Y-m-d'));
    }

    public function testResolveShadowData(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setShadowLocale('de');

        $result = $this->resolver->resolve($exampleDimension);
        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame('de', $content['shadowBaseLocale']);
    }

    public function testResolveLastModifiedData(): void
    {
        $example = new Example();
        $exampleDimension = new ExampleDimensionContent($example);
        $exampleDimension->setLastModified(new \DateTimeImmutable('2021-01-01'));

        $result = $this->resolver->resolve($exampleDimension);
        /** @var SettingsData $content */
        $content = $result->getContent();

        self::assertSame('2021-01-01', $content['lastModified']?->format('Y-m-d'));
    }
}
