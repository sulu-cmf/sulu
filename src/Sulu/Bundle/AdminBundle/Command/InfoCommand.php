<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'sulu:admin:info')]
class InfoCommand extends Command
{
    public function __construct(private string $suluVersion)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $docsVersion = 'latest';
        if (false !== \strpos($this->suluVersion, '.')) {
            $separators = \explode('.', $this->suluVersion);
            $parsedVersion = $separators[0] . '.' . $separators[1];
            if (\is_numeric($parsedVersion)) {
                $docsVersion = $parsedVersion;
            }
        }

        $output->getFormatter()->setStyle('title', new OutputFormatterStyle('green'));
        $output->getFormatter()->setStyle('subtitle', new OutputFormatterStyle('blue'));
        $output->getFormatter()->setStyle('star', new OutputFormatterStyle('yellow'));

        $text = <<<EOT
<title>Welcome to the Sulu CMS 👋</title>
<title>==========================</title>

<subtitle>📘 Documentation</subtitle>
<subtitle>-----------------</subtitle>

 - Sulu Documentation: <href=https://docs.sulu.io/>https://docs.sulu.io/</>
 - Symfony Documentation: <href=https://symfony.com/doc/current/index.html>https://symfony.com/doc/current/index.html</>
 - Doctrine ORM Documentation: <href=https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/index.html>https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/index.html</>

<subtitle>📦 Examples, Code and Bundles</subtitle>
<subtitle>-----------------------------</subtitle>

 - Sulu Demo Project: <href=https://github.com/sulu/sulu-demo/>https://github.com/sulu/sulu-demo/</>
 - Sulu Workshop Project: <href=https://github.com/sulu/sulu-workshop/>https://github.com/sulu/sulu-workshop/</>
 - Sulu Source Code: <href=https://github.com/sulu/sulu/>https://github.com/sulu/sulu/</>
 - Official Sulu Bundles: <href=https://github.com/sulu?q=Bundle>https://github.com/sulu?q=Bundle</>

<subtitle>📺 News and Updates</subtitle>
<subtitle>-------------------</subtitle>

 - Sulu Blog: <href=https://sulu.io/know-how/blog>https://sulu.io/know-how/blog</>
 - Sulu Guides: <href=https://sulu.io/know-how/guides>https://sulu.io/know-how/guides</>
 - Sulu Newsletter: <href=https://sulu.io/#newsletter>https://sulu.io/#newsletter</>

<subtitle>🤝 Community</subtitle>
<subtitle>------------</subtitle>

 - Github Issues: <href=https://github.com/sulu/sulu/issues>https://github.com/sulu/sulu/issues</>
 - Github Discussions: <href=https://github.com/sulu/sulu/discussions>https://github.com/sulu/sulu/discussions</>
 - Stackoverflow: <href=https://stackoverflow.com/tags/sulu>https://stackoverflow.com/tags/sulu</>
 - Sulu Slack Channel: <href=https://sulu.io/services-and-support#chat>https://sulu.io/services-and-support#chat</>
 - Symfony Slack Channel: <href=https://symfony.com/community>https://symfony.com/community</>
 - Doctrine Slack Channel: <href=https://www.doctrine-project.org/community/index.html>https://www.doctrine-project.org/community/index.html</>
 - FriendsOfSulu: <href=https://github.com/FriendsOfSulu>https://github.com/FriendsOfSulu</>

---

Continue with the "Getting Started" documentation to setup your project:

 - <href=https://docs.sulu.io/en/%s/book/getting-started.html>https://docs.sulu.io/en/%s/book/getting-started.html</> 🚀


<star>If you like Sulu, don't hesitate to spread some love and leave a star ⭐ on GitHub: <href=https://github.com/sulu/sulu>https://github.com/sulu/sulu</></star>
EOT;

        $text = \sprintf(
            \str_replace("\n", \PHP_EOL, $text),
            $docsVersion,
            $docsVersion
        );

        $output->writeln($text);

        return 0;
    }
}
