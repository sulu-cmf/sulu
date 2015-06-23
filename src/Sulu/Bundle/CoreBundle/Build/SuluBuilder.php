<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

use Massive\Bundle\BuildBundle\Build\BuilderContext;
use Massive\Bundle\BuildBundle\Build\BuilderInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract builder for ALL sulu builders.
 */
abstract class SuluBuilder implements ContainerAwareInterface, BuilderInterface
{
    protected $container;
    protected $output;
    protected $input;
    protected $application;

    /**
     * {@inheritDoc}
     */
    public function setContext(BuilderContext $context)
    {
        $this->input = $context->getInput();
        $this->output = $context->getOutput();
        $this->application = $context->getApplication();
        $style = new OutputFormatterStyle('blue', 'black', array('bold'));
        $this->output->getFormatter()->setStyle('section', $style);
    }

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Execute a command.
     */
    protected function execCommand($description, $command, $args = array(''))
    {
        $this->output->getFormatter()->setIndentLevel(1);
        $formatter = new FormatterHelper();

        if (!empty($args)) {
            $this->output->writeln(sprintf('<comment>%s </comment> (%s)', $command, json_encode($args)));
        } else {
            $this->output->writeln(sprintf('<comment>%s </comment> (%s)', $command));
        }
        $this->output->writeln('');

        $args['command'] = $command;
        $command = $this->application->find($command);
        $input = new ArrayInput($args);
        $input->setInteractive(false);

        $this->output->getFormatter()->setIndentLevel(2);
        $res = $command->run($input, $this->output);
        $this->output->writeln('');

        return $res;
    }
}
