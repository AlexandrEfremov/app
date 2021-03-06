<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2011 Nicholas J Humfrey.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. The name of the author 'Nicholas J Humfrey" may be used to endorse or
 *    promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2011 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @version    $Id: Resource.php 98993 2011-10-05 12:20:17Z bkaempgen $
 */

/**
 * Class that represents an RDF resource
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2011 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Resource
{
    /** The URI for this resource */
    private $_uri = null;

    /** The Graph that this resource belongs to */
    private $_graph = null;


    /** Constructor
     *
     * * Please do not call new EasyRdf_Resource() directly *
     *
     * To create a new resource use the get method in a graph:
     * $resource = $graph->resource('http://www.example.com/');
     *
     */
    public function __construct($uri, $graph=null)
    {
        if (!is_string($uri) or $uri == null or $uri == '') {
            throw new InvalidArgumentException(
                "\$uri should be a string and cannot be null or empty"
            );
        }

        $this->_uri = $uri;

        # FIXME: check that $graph is an EasyRdf_Graph object
        $this->_graph = $graph;
    }

    /** Returns the URI for the resource.
     *
     * @return string  URI of this resource.
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /** Check to see if a resource is a blank node.
     *
     * @return bool True if this resource is a blank node.
     */
    public function isBnode()
    {
        if (substr($this->_uri, 0, 2) == '_:') {
            return true;
        } else {
            return false;
        }
    }

    /** Get the identifier for a blank node
     *
     * Returns null if the resource is not a blank node.
     *
     * @return string The identifer for the bnode
     */
    public function getNodeId()
    {
        if (substr($this->_uri, 0, 2) == '_:') {
            return substr($this->_uri, 2);
        } else {
            return null;
        }
    }

    /** Get a the prefix of the namespace that this resource is part of
     *
     * This method will return null the resource isn't part of any
     * registered namespace.
     *
     * @return string The namespace prefix of the resource (e.g. foaf)
     */
    public function prefix()
    {
        return EasyRdf_Namespace::prefixOfUri($this->_uri);
    }

    /** Get a shortened version of the resources URI.
     *
     * This method will return the full URI if the resource isn't part of any
     * registered namespace.
     *
     * @return string The shortened URI of this resource (e.g. foaf:name)
     */
    public function shorten()
    {
        return EasyRdf_Namespace::shorten($this->_uri);
    }

    /** Returns the properties of the resource as an associative array
     *
     * For example:
     * array('type' => 'uri', 'value' => 'http://www.example.com/')
     *
     * @return array  The properties of the resource
     */
    public function toArray()
    {
        if ($this->isBnode())
            return array('type' => 'bnode', 'value' => $this->_uri);
        else
            return array('type' => 'uri', 'value' => $this->_uri);
    }

    /** Return pretty-print view of the resource
     *
     * @param  bool   $html  Set to true to format the dump using HTML
     * @param  string $color The colour of the text
     * @return string
     */
    public function dumpValue($html=true, $color='blue')
    {
        return EasyRdf_Utils::dumpResourceValue($this, $html, $color);
    }

    /** Magic method to return URI of resource when casted to string
     *
     * @return string The URI of the resource
     */
    public function __toString()
    {
        return $this->_uri;
    }



    /** Throw can exception if the resource does not belong to a graph
     *  @ignore
     */
    protected function checkHasGraph()
    {
        if (!$this->_graph) {
            throw new EasyRdf_Exception(
                "EasyRdf_Resource is not part of a graph."
            );
        }
    }

    /** Set value(s) for a property
     *
     * The new value(s) will replace the existing values for the property.
     * The name of the property should be a string.
     * If you set a property to null or an empty array, then the property
     * will be deleted.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  mixed   $values   The value(s) for the property.
     * @return array             Array of new values for this property.
     */
    public function set($property, $values)
    {
        $this->checkHasGraph();
        return $this->_graph->set($this->_uri, $property, $values);
    }

    /** Delete a property (or optionally just a specific value)
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  object  $value The value to delete (null to delete all values)
     * @return null
     */
    public function delete($property, $value=null)
    {
        $this->checkHasGraph();
        return $this->_graph->delete($this->_uri, $property, $value);
    }

    /** Add values to for a property of the resource
     *
     * The value can either be a single value or an array of values.
     *
     * Example:
     *   $resource->add('prefix:property', 'value');
     *
     * @param  mixed $resource   The resource to add data to
     * @param  mixed $property   The property name
     * @param  mixed $value      The value for the property
     * @return array             Array of all values associated with property.
     */
    public function add($property, $values=null)
    {
        $this->checkHasGraph();
        return $this->_graph->add($this->_uri, $property, $values);
    }

    /** Add a literal value as a property of the resource
     *
     * The value can either be a single value or an array of values.
     *
     * Example:
     *   $resource->add('dc:title', 'Title of Page');
     *
     * @param  mixed  $property  The property name
     * @param  mixed  $value     The value or values for the property
     * @param  string $lang      The language of the literal
     */
    public function addLiteral($property, $values, $lang=null)
    {
        $this->checkHasGraph();
        return $this->_graph->addLiteral($this->_uri, $property, $values, $lang);
    }

    /** Add a resource as a property of the resource
     *
     * Example:
     *   $bob->add('foaf:knows', 'http://example.com/alice');
     *
     * @param  mixed $property   The property name
     * @param  mixed $resource2  The resource to be value of the property
     */
    public function addResource($property, $values)
    {
        $this->checkHasGraph();
        return $this->_graph->addResource($this->_uri, $property, $values);
    }

    /** Get a single value for a property
     *
     * If multiple values are set for a property then the value returned
     * may be arbitrary.
     *
     * If $property is an array, then the first item in the array that matches
     * a property that exists is returned.
     *
     * This method will return null if the property does not exist.
     *
     * @param  string|array $property The name of the property (e.g. foaf:name)
     * @param  string       $lang     The language to filter by (e.g. en)
     * @return mixed                  A value associated with the property
     */
    public function get($property, $type=null, $lang=null)
    {
        $this->checkHasGraph();
        return $this->_graph->get($this->_uri, $property, $type, $lang);
    }

    /** Get a single literal value for a property of the resource
     *
     * If multiple values are set for a property then the value returned
     * may be arbitrary.
     *
     * This method will return null if there is not literal value for the
     * property.
     *
     * @param  string       $resource The URI of the resource (e.g. http://example.com/joe#me)
     * @param  string|array $property The name of the property (e.g. foaf:name)
     * @param  string       $lang     The language to filter by (e.g. en)
     * @return object EasyRdf_Literal Literal value associated with the property
     */
    public function getLiteral($property, $lang=null)
    {
        $this->checkHasGraph();
        return $this->_graph->get($this->_uri, $property, 'literal', $lang);
    }

    /** Get a single resource value for a property of the resource
     *
     * If multiple values are set for a property then the value returned
     * may be arbitrary.
     *
     * This method will return null if there is not resource for the
     * property.
     *
     * @param  string|array $property The name of the property (e.g. foaf:name)
     * @return object EasyRdf_Resource Resource associated with the property
     */
    public function getResource($property)
    {
        $this->checkHasGraph();
        return $this->_graph->get($this->_uri, $property, 'resource');
    }

    /** Get all values for a property
     *
     * This method will return an empty array if the property does not exist.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  string  $type     The type of value to filter by (e.g. literal)
     * @param  string  $lang     The language to filter by (e.g. en)
     * @return array             An array of values associated with the property
     */
    public function all($property, $type=null, $lang=null)
    {
        $this->checkHasGraph();
        return $this->_graph->all($this->_uri, $property, $type, $lang);
    }

    /** Get all literal values for a property of the resource
     *
     * This method will return an empty array if the resource does not
     * has any literal values for that property.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  string  $lang     The language to filter by (e.g. en)
     * @return array             An array of values associated with the property
     */
    public function allLiterals($property, $lang=null)
    {
        $this->checkHasGraph();
        return $this->_graph->all($this->_uri, $property, 'literal', $lang);
    }

    /** Get all resources for a property of the resource
     *
     * This method will return an empty array if the resource does not
     * has any resources for that property.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @return array             An array of values associated with the property
     */
    public function allResources($property)
    {
        $this->checkHasGraph();
        return $this->_graph->all($this->_uri, $property, 'resource');
    }

    /** Concatenate all values for a property into a string.
     *
     * The default is to join the values together with a space character.
     * This method will return an empty string if the property does not exist.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  string  $glue     The string to glue the values together with.
     * @param  string  $lang     The language to filter by (e.g. en)
     * @return string            Concatenation of all the values.
     */
    public function join($property, $glue=' ', $lang=null)
    {
        $this->checkHasGraph();
        return $this->_graph->join($this->_uri, $property, $glue, $lang);
    }

    /** Get a list of the full URIs for the properties of this resource.
     *
     * This method will return an empty array if the resource has no properties.
     *
     * @return array            Array of full URIs
     */
    public function propertyUris()
    {
        $this->checkHasGraph();
        return $this->_graph->propertyUris($this->_uri);
    }

    /** Get a list of all the shortened property names (qnames) for a resource.
     *
     * This method will return an empty array if the resource has no properties.
     *
     * @return array            Array of shortened URIs
     */
    public function properties()
    {
        $this->checkHasGraph();
        return $this->_graph->properties($this->_uri);
    }

    /** Get a list of the full URIs for the properties that point to this resource.
     *
     * @return array   Array of full property URIs
     */
    public function reversePropertyUris()
    {
        $this->checkHasGraph();
        return $this->_graph->reversePropertyUris($this->_uri);
    }

    /** Check to see if a property exists for this resource.
     *
     * This method will return true if the property exists.
     *
     * @param  string  $property The name of the property (e.g. foaf:gender)
     * @return bool              True if value the property exists.
     */
    public function hasProperty($property)
    {
        $this->checkHasGraph();
        return $this->_graph->hasProperty($this->_uri, $property);
    }

    /** Get a list of types for a resource.
     *
     * The types will each be a shortened URI as a string.
     * This method will return an empty array if the resource has no types.
     *
     * @return array All types assocated with the resource (e.g. foaf:Person)
     */
    public function types()
    {
        $this->checkHasGraph();
        return $this->_graph->types($this->_uri);
    }

    /** Get a single type for a resource.
     *
     * The type will be a shortened URI as a string.
     * If the resource has multiple types then the type returned
     * may be arbitrary.
     * This method will return null if the resource has no type.
     *
     * @return string A type assocated with the resource (e.g. foaf:Person)
     */
    public function type()
    {
        $this->checkHasGraph();
        return $this->_graph->type($this->_uri);
    }

    /** Get a single type for a resource, as a resource.
     *
     * The type will be returned as an EasyRdf_Resource.
     * If the resource has multiple types then the type returned
     * may be arbitrary.
     * This method will return null if the resource has no type.
     *
     * @return EasyRdf_Resource A type assocated with the resource.
     */
    public function typeAsResource()
    {
        return $this->_graph->typeAsResource($this->_uri);
    }

    /** Check if a resource is of the specified type
     *
     * @param  string  $type The type to check (e.g. foaf:Person)
     * @return boolean       True if resource is of specified type.
     */
    public function is_a($type)
    {
        $this->checkHasGraph();
        return $this->_graph->is_a($this->_uri, $type);
    }

    /** Add one or more rdf:type properties to the resource
     *
     * @param  string  $type     The new type (e.g. foaf:Person)
     */
    public function addType($types)
    {
        $this->checkHasGraph();
        return $this->_graph->addType($this->_uri, $types);
    }

    /** Change the rdf:type property for the resource
     *
     * Note that the PHP class of the resource will not change.
     *
     * @param  string  $type     The new type (e.g. foaf:Person)
     */
    public function setType($type)
    {
        $this->checkHasGraph();
        return $this->_graph->setType($this->_uri, $type);
    }

    /** Get the primary topic of this resource.
     *
     * Returns null if no primary topic is available.
     *
     * @return EasyRdf_Resource The primary topic of this resource.
     */
    public function primaryTopic()
    {
        $this->checkHasGraph();
        return $this->_graph->primaryTopic($this->_uri);
    }

    /** Get a human readable label for this resource
     *
     * This method will check a number of properties for the resource
     * (in the order: skos:prefLabel, rdfs:label, foaf:name, dc:title)
     * and return an approriate first that is available. If no label
     * is available then it will return null.
     *
     * @return string A label for the resource.
     */
    public function label($lang=null)
    {
        $this->checkHasGraph();
        return $this->_graph->label($this->_uri, $lang);
    }

    /** Return a human readable view of the resource and its properties
     *
     * This method is intended to be a debugging aid and will
     * print a resource and its properties.
     *
     * @param  bool  $html  Set to true to format the dump using HTML
     * @return string
     */
    public function dump($html=true)
    {
        $this->checkHasGraph();
        return $this->_graph->dumpResource($this->_uri, $html);
    }
}

