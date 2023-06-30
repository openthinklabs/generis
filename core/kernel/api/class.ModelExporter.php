<?php

use EasyRdf\Format;
use EasyRdf\Graph;
use oat\generis\model\data\ModelManager;

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
 * Copyright (c) 2014 (original work) Open Assessment Technologies SA;
 *
 *
 */

class core_kernel_api_ModelExporter
{
    /**
     * Export the entire ontology
     *
     * @param string $format (optional) which format resulted ontology will be.
     *
     * @return string
     */
    public static function exportAll($format = 'rdfxml')
    {
        $dbWrapper = core_kernel_classes_DbWrapper::singleton();
        $result = $dbWrapper->query('SELECT DISTINCT "subject", "predicate", "object", "l_language" FROM "statements"');
        return self::statement2rdf($result, $format);
    }

    /**
     * Export models by id
     *
     * @param array $modelIds
     * @param string $format (optional) which format resulted models ontology will be.
     *
     * @return string
     */
    public static function exportModels($modelIds, $format = 'rdfxml')
    {
        $dbWrapper = core_kernel_classes_DbWrapper::singleton();
        $result = $dbWrapper->query('SELECT DISTINCT "subject", "predicate", "object", "l_language" FROM "statements" 
            WHERE "modelid" IN (\'' . implode('\',\'', $modelIds) . '\')');

        common_Logger::i('Found ' . $result->rowCount() . ' entries for models ' . implode(',', $modelIds));
        return self::statement2rdf($result, $format);
    }

    /**
     * @return string
     */
    public static function exportModelByUri()
    {
        return self::exportModels(
            self::getOntology()->getReadableModels()
        );
    }

    /**
     * @throws \EasyRdf\Exception
     * @ignore
     */
    private static function statement2rdf($statement, $format = 'rdfxml')
    {
        $graph = new Graph();
        while ($r = $statement->fetch()) {
            if (isset($r['l_language']) && !empty($r['l_language'])) {
                $graph->addLiteral($r['subject'], $r['predicate'], $r['object'], $r['l_language']);
            } elseif (common_Utils::isUri($r['object'])) {
                $graph->addResource($r['subject'], $r['predicate'], $r['object']);
            } else {
                $graph->addLiteral($r['subject'], $r['predicate'], $r['object']);
            }
        }

        $format = Format::getFormat($format);

        return $graph->serialise($format);
    }

    private static function getOntology(): core_kernel_persistence_smoothsql_SmoothModel
    {
        return ModelManager::getModel();
    }
}
