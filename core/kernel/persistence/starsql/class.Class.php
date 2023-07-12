<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2023 (original work) Open Assessment Technologies SA ;
 */

declare(strict_types=1);

use oat\generis\model\data\event\ClassPropertyCreatedEvent;
use oat\generis\model\GenerisRdf;
use oat\generis\model\OntologyRdf;
use oat\generis\model\OntologyRdfs;
use oat\oatbox\event\EventManagerAwareTrait;
use oat\generis\model\kernel\uri\UriProvider;
use function WikibaseSolutions\CypherDSL\node;
use function WikibaseSolutions\CypherDSL\parameter;
use function WikibaseSolutions\CypherDSL\query;
use function WikibaseSolutions\CypherDSL\variable;

class core_kernel_persistence_starsql_Class extends core_kernel_persistence_starsql_Resource implements core_kernel_persistence_ClassInterface
{
    use EventManagerAwareTrait;

    public function getSubClasses(core_kernel_classes_Class $resource, $recursive = false)
    {
        $uri = $resource->getUri();
        $relationship = OntologyRdfs::RDFS_SUBCLASSOF;
        if (!empty($recursive)) {
            $query = <<<CYPHER
                MATCH (startNode:Resource {uri: \$uri})
                MATCH path = (descendantNode)-[:`{$relationship}`*]->(startNode)
                RETURN descendantNode.uri
CYPHER;
        } else {
            $query = <<<CYPHER
                MATCH (startNode:Resource {uri: \$uri})
                MATCH path = (descendantNode)-[:`{$relationship}`]->(startNode)
                RETURN descendantNode.uri
CYPHER;
        }

//        \common_Logger::i('getSubClasses(): ' . var_export($query, true));
        $results = $this->getPersistence()->run($query, ['uri' => $uri]);
        $returnValue = [];
        foreach ($results as $result) {
            $uri = $result->current();
            if (!$uri) {
                continue;
            }
            $subClass = $this->getModel()->getClass($uri);
            $returnValue[$subClass->getUri()] = $subClass ;
        }

        return $returnValue;
    }

    public function isSubClassOf(core_kernel_classes_Class $resource, core_kernel_classes_Class $parentClass)
    {
        // @TODO would it be worth it to check direct relationship of node:IS_SUBCLASS_OF?
        $parentSubClasses = $parentClass->getSubClasses(true);
        foreach ($parentSubClasses as $subClass) {
            if ($subClass->getUri() === $resource->getUri()) {
                return true;
            }
        }

        return false;
    }

    public function getParentClasses(core_kernel_classes_Class $resource, $recursive = false)
    {
        $uri = $resource->getUri();
        $relationship = OntologyRdfs::RDFS_SUBCLASSOF;
        if (!empty($recursive)) {
            $query = <<<CYPHER
                MATCH (startNode:Resource {uri: \$uri})
                MATCH path = (startNode)-[:`{$relationship}`*]->(ancestorNode)
                RETURN ancestorNode.uri
CYPHER;
        } else {
            $query = <<<CYPHER
                MATCH (startNode:Resource {uri: \$uri})
                MATCH path = (startNode)-[:`{$relationship}`]->(ancestorNode)
                RETURN ancestorNode.uri
CYPHER;
        }

        $results = $this->getPersistence()->run($query, ['uri' => $uri]);
        $returnValue = [];
        foreach ($results as $result) {
            $uri = $result->current();
            $parentClass = $this->getModel()->getClass($uri);
            $returnValue[$parentClass->getUri()] = $parentClass ;
        }

        return $returnValue;
    }

    public function getProperties(core_kernel_classes_Class $resource, $recursive = false)
    {
        throw new common_Exception('Not implemented! ' . __FILE__ . ' line: ' . __LINE__);
    }

    public function getInstances(core_kernel_classes_Class $resource, $recursive = false, $params = [])
    {
        throw new common_Exception('Not implemented! ' . __FILE__ . ' line: ' . __LINE__);
    }

    /**
     * @deprecated
     */
    public function setInstance(core_kernel_classes_Class $resource, core_kernel_classes_Resource $instance)
    {
        throw new common_exception_DeprecatedApiMethod(__METHOD__ . ' is deprecated. ');
    }

    public function setSubClassOf(core_kernel_classes_Class $resource, core_kernel_classes_Class $iClass): bool
    {
        $subClassOf = $this->getModel()->getProperty(OntologyRdfs::RDFS_SUBCLASSOF);
        $returnValue = $this->setPropertyValue($resource, $subClassOf, $iClass->getUri());

        return (bool) $returnValue;
    }

    /**
     * @deprecated
     */
    public function setProperty(core_kernel_classes_Class $resource, core_kernel_classes_Property $property)
    {
        throw new common_exception_DeprecatedApiMethod(__METHOD__ . ' is deprecated. ');
    }

    public function createInstance(core_kernel_classes_Class $resource, $label = '', $comment = '', $uri = '')
    {
        $subject = '';
        if ($uri == '') {
            $subject = $this->getServiceLocator()->get(UriProvider::SERVICE_ID)->provide();
        } elseif ($uri[0] == '#') { //$uri should start with # and be well formed
            $modelUri = common_ext_NamespaceManager::singleton()->getLocalNamespace()->getUri();
            $subject = rtrim($modelUri, '#') . $uri;
        } else {
            $subject = $uri;
        }

        $node = node()->addProperty('uri', $uriParameter = parameter())
            ->addLabel('Resource');
        if (!empty($label)) {
            $node->addProperty(OntologyRdfs::RDFS_LABEL, $label);
        }
        if (!empty($comment)) {
            $node->addProperty(OntologyRdfs::RDFS_COMMENT, $comment);
        }

        $nodeForRelationship = node()->withVariable($variableForRelatedResource = variable());
        $relatedResource = node('Resource')->withProperties(['uri' => $relatedUri = parameter()])->withVariable($variableForRelatedResource);
        $node = $node->relationshipTo($nodeForRelationship, OntologyRdf::RDF_TYPE);

        $query = query()
            ->match($relatedResource)
            ->create($node);
        $results = $this->getPersistence()->run(
            $query->build(),
            [$uriParameter->getParameter() => $subject, $relatedUri->getParameter() => $resource->getUri()]
        );

        return $this->getModel()->getResource($subject);
    }

    /**
     * (non-PHPdoc)
     * @see core_kernel_persistence_ClassInterface::createSubClass()
     */
    public function createSubClass(core_kernel_classes_Class $resource, $label = '', $comment = '', $uri = '')
    {
        if (!empty($uri)) {
            common_Logger::w('Use of parameter uri in ' . __METHOD__ . ' is deprecated');
        }
        $uri = empty($uri) ? $this->getServiceLocator()->get(UriProvider::SERVICE_ID)->provide() : $uri;
        $returnValue = $this->getModel()->getClass($uri);
        $properties = [
            OntologyRdfs::RDFS_SUBCLASSOF => $resource,
        ];
        if (!empty($label)) {
            $properties[OntologyRdfs::RDFS_LABEL] = $label;
        }
        if (!empty($comment)) {
            $properties[OntologyRdfs::RDFS_COMMENT] = $comment;
        }

        $returnValue->setPropertiesValues($properties);
        return $returnValue;
    }

    public function createProperty(core_kernel_classes_Class $resource, $label = '', $comment = '', $isLgDependent = false)
    {
        $returnValue = null;

        $propertyClass = $this->getModel()->getClass(OntologyRdf::RDF_PROPERTY);
        $properties = [
            OntologyRdfs::RDFS_DOMAIN => $resource->getUri(),
            GenerisRdf::PROPERTY_IS_LG_DEPENDENT => ((bool)$isLgDependent) ?  GenerisRdf::GENERIS_TRUE : GenerisRdf::GENERIS_FALSE
        ];
        if (!empty($label)) {
            $properties[OntologyRdfs::RDFS_LABEL] = $label;
        }
        if (!empty($comment)) {
            $properties[OntologyRdfs::RDFS_COMMENT] = $comment;
        }
        $propertyInstance = $propertyClass->createInstanceWithProperties($properties);

        $returnValue = $this->getModel()->getProperty($propertyInstance->getUri());

        $this->getEventManager()->trigger(
            new ClassPropertyCreatedEvent(
                $resource,
                [
                    'propertyUri' => $propertyInstance->getUri(),
                    'propertyLabel' => $propertyInstance->getLabel()
                ]
            )
        );

        return $returnValue;
    }

    /**
     * @deprecated
     */
    public function searchInstances(core_kernel_classes_Class $resource, $propertyFilters = [], $options = [])
    {
        throw new common_Exception('Not implemented! ' . __FILE__ . ' line: ' . __LINE__);
    }

    public function countInstances(core_kernel_classes_Class $resource, $propertyFilters = [], $options = [])
    {
        throw new common_Exception('Not implemented! ' . __FILE__ . ' line: ' . __LINE__);
    }

    public function getInstancesPropertyValues(core_kernel_classes_Class $resource, core_kernel_classes_Property $property, $propertyFilters = [], $options = [])
    {
        throw new common_Exception('Not implemented! ' . __FILE__ . ' line: ' . __LINE__);
    }

    /**
     * @deprecated
     */
    public function unsetProperty(core_kernel_classes_Class $resource, core_kernel_classes_Property $property)
    {
        throw new common_exception_DeprecatedApiMethod(__METHOD__ . ' is deprecated. ');
    }

    public function createInstanceWithProperties(core_kernel_classes_Class $type, $properties)
    {
        $returnValue = null;

        if (isset($properties[OntologyRdf::RDF_TYPE])) {
            throw new core_kernel_persistence_Exception('Additional types in createInstanceWithProperties not permited');
        }

        $properties[OntologyRdf::RDF_TYPE] = $type;
        $returnValue = $this->getModel()->getResource($this->getServiceLocator()->get(UriProvider::SERVICE_ID)->provide());
        $returnValue->setPropertiesValues($properties);

        return $returnValue;
    }

    public function deleteInstances(core_kernel_classes_Class $resource, $resources, $deleteReference = false)
    {
        throw new common_Exception('Not implemented! ' . __FILE__ . ' line: ' . __LINE__);
    }

    public function getFilteredQuery(core_kernel_classes_Class $resource, $propertyFilters = [], $options = []): string
    {
        throw new common_Exception('Not implemented! ' . __FILE__ . ' line: ' . __LINE__);
    }
}
