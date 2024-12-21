<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Subscriber\PHPCR;

use PHPCR\ItemInterface;
use PHPCR\ItemVisitorInterface;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;

class CleanupNode implements \IteratorAggregate, NodeInterface
{
    private array $writtenProperties = [];

    public function __construct(
        private NodeInterface $node,
    ) {
    }

    public function getWrittenPropertyKeys()
    {
        return \array_keys($this->writtenProperties);
    }

    public function getPath()
    {
        return $this->node->getPath();
    }

    public function getName()
    {
        return $this->node->getName();
    }

    public function getAncestor($depth)
    {
        return $this->node->getAncestor($depth);
    }

    public function getParent()
    {
        return $this->node->getParent();
    }

    public function getDepth()
    {
        return $this->node->getDepth();
    }

    public function getSession()
    {
        return $this->node->getSession();
    }

    public function isNode()
    {
        return $this->node->isNode();
    }

    public function isNew()
    {
        return $this->node->isNew();
    }

    public function isModified()
    {
        return $this->node->isModified();
    }

    public function isSame(ItemInterface $otherItem)
    {
        return $this->node->isSame($otherItem);
    }

    public function accept(ItemVisitorInterface $visitor)
    {
        $this->node->accept($visitor);
    }

    public function revert()
    {
        $this->node->revert();
    }

    public function remove()
    {
        $this->node->remove();
    }

    public function addNode($relPath, $primaryNodeTypeName = null)
    {
        return $this->node->addNode($relPath, $primaryNodeTypeName);
    }

    public function addNodeAutoNamed($nameHint = null, $primaryNodeTypeName = null)
    {
        return $this->node->addNodeAutoNamed($nameHint, $primaryNodeTypeName);
    }

    public function orderBefore($srcChildRelPath, $destChildRelPath)
    {
        $this->node->orderBefore($srcChildRelPath, $destChildRelPath);
    }

    public function rename($newName)
    {
        $this->node->rename($newName);
    }

    public function setProperty($name, $value, $type = PropertyType::UNDEFINED)
    {
        $this->writtenProperties[$name] = ['value' => $value, 'type' => $type];

        return $this->node->setProperty($name, $value, $type);
    }

    public function getNode($relPath)
    {
        return $this->node->getNode($relPath);
    }

    public function getNodes($nameFilter = null, $typeFilter = null)
    {
        return $this->node->getNodes($nameFilter, $typeFilter);
    }

    public function getNodeNames($nameFilter = null, $typeFilter = null)
    {
        return $this->node->getNodeNames($nameFilter, $typeFilter);
    }

    public function getProperty($relPath)
    {
        return $this->node->getProperty($relPath);
    }

    public function getPropertyValue($name, $type = null)
    {
        return $this->node->getPropertyValue($name, $type);
    }

    public function getPropertyValueWithDefault($relPath, $defaultValue)
    {
        return $this->node->getPropertyValueWithDefault($relPath, $defaultValue);
    }

    public function getProperties($nameFilter = null)
    {
        return $this->node->getProperties($nameFilter);
    }

    public function getPropertiesValues($nameFilter = null, $dereference = true)
    {
        return $this->node->getPropertiesValues($nameFilter, $dereference);
    }

    public function getPrimaryItem()
    {
        return $this->node->getPrimaryItem();
    }

    public function getIdentifier()
    {
        return $this->node->getIdentifier();
    }

    public function getIndex()
    {
        return $this->node->getIndex();
    }

    public function getReferences($name = null)
    {
        return $this->node->getReferences($name);
    }

    public function getWeakReferences($name = null)
    {
        return $this->node->getWeakReferences($name);
    }

    public function hasNode($relPath)
    {
        return $this->node->hasNode($relPath);
    }

    public function hasProperty($relPath)
    {
        return $this->node->hasProperty($relPath);
    }

    public function hasNodes()
    {
        return $this->node->hasNodes();
    }

    public function hasProperties()
    {
        return $this->node->hasProperties();
    }

    public function getPrimaryNodeType()
    {
        return $this->node->getPrimaryNodeType();
    }

    public function getMixinNodeTypes()
    {
        return $this->node->getMixinNodeTypes();
    }

    public function isNodeType($nodeTypeName)
    {
        return $this->node->isNodeType($nodeTypeName);
    }

    public function setPrimaryType($nodeTypeName)
    {
        $this->node->setPrimaryType($nodeTypeName);
    }

    public function addMixin($mixinName)
    {
        $this->node->addMixin($mixinName);
    }

    public function removeMixin($mixinName)
    {
        $this->node->removeMixin($mixinName);
    }

    public function setMixins(array $mixinNames)
    {
        $this->node->setMixins($mixinNames);
    }

    public function canAddMixin($mixinName)
    {
        return $this->node->canAddMixin($mixinName);
    }

    public function getDefinition()
    {
        return $this->node->getDefinition();
    }

    public function update($srcWorkspace)
    {
        $this->node->update($srcWorkspace);
    }

    public function getCorrespondingNodePath($workspaceName)
    {
        return $this->node->getCorrespondingNodePath($workspaceName);
    }

    public function getSharedSet()
    {
        return $this->node->getSharedSet();
    }

    public function removeSharedSet()
    {
        $this->node->removeSharedSet();
    }

    public function removeShare()
    {
        $this->node->removeShare();
    }

    public function isCheckedOut()
    {
        return $this->node->isCheckedOut();
    }

    public function isLocked()
    {
        return $this->node->isLocked();
    }

    public function followLifecycleTransition($transition)
    {
        $this->node->followLifecycleTransition($transition);
    }

    public function getAllowedLifecycleTransitions()
    {
        return $this->node->getAllowedLifecycleTransitions();
    }

    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->node->getIterator();
    }
}
