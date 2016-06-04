<?php

namespace Vector88\WebUtils;

class XmlHelper {
	
	private $_rootElement;
	private $_xpath;
	private $_namespaces;
	
	public function __construct() {
		$this->_namespaces = array();
	}
	
	public function create() {
		$this->setElement( new \DOMDocument() );
		return $this;
	}
	
	public function setElement( $document ) {
		$this->_rootElement = $document;
		$this->_initializeXPath();
		return $this;
	}
	
	public function loadStream( $xmlStream ) {
		$this->_rootElement = new \DOMDocument();
		$loaded = $this->_rootElement->loadXML( $xmlStream );
		
		if( !$loaded ) {
			throw new \Exception( "The provided XML stream does not contain a valid XML document." );
		}

		$this->_initializeXPath();
		
		return $this;
	}
	
	private function _getDocument() {
		$document = null;
		if( $this->_rootElement instanceof \DOMDocument ) {
			return $this->_rootElement;
			
		} else if( $this->_rootElement instanceof \DOMNode ) {
			return $this->_rootElement->ownerDocument;
		}
		
		return null;
	}
	
	private function _initializeXPath() {
		$document = $this->_getDocument();
		if( null === $document ) {
			throw new \Exception( "Cannot initialize XPath without a DOMDocument." );
		}
		
		$this->_xpath = new \DOMXPath( $document );
		foreach( $this->_namespaces as $prefix => $namespace ) {
			$this->_xpath->registerNamespace( $prefix, $namespace );
		}
	}
	
	public function withNamespace( $namespace, $prefix = "" ) {
		$this->_namespaces[ $prefix ] = $namespace;
		if( null !== $this->_xpath ) {
			$this->_xpath->registerNamespace( $prefix, $namespace );
		}
		return $this;
	}
	
	public function withNamespaces( $namespaces = array() ) {
		foreach( $namespaces as $prefix => $namespace ) {
			$this->withNamespace( $namespace, $prefix );
		}
		return $this;
	}
	
	public function toString() {
		$this->_rootElement->formatOutput = true;
		return $this->_getDocument()->saveXML( $this->_rootElement );
	}
	
	public function findFirst( $xpath, $parent = null ) {
		if( null === $parent ) {
			$parent = $this->_rootElement;
		}
		
		$nodes = $this->_xpath->query( $xpath, $parent );
		if( $nodes->length > 0 ) {
			return $nodes->item( 0 );
		}
		
		return null;
	}
	
	public function findAll( $xpath, $parent = null ) {
		if( null === $parent ) {
			$parent = $this->_rootElement;
		}
		
		$nodes = $this->_xpath->query( $xpath, $parent );
		$results = array();
		foreach( $nodes as $node ) {
			$results[] = $node;
		}
		return $results;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	//
	// XML Node Retrieval
	//
	
	public function element( $elementName, $parent = null ) {
		return $this->findFirst( "./{$elementName}", $parent );
	}
	
	public function elements( $elementName, $parent = null ) {
		return $this->findAll( "./{$elementName}", $parent );
	}
	
	public function attribute( $attributeName, $parent = null ) {
		if( null === $parent ) {
			$parent = $this->_rootElement;
		}
		
		if( $parent->hasAttribute( $attributeName ) ) {
			return $parent->getAttribute( $attributeName );
		}
		return null;
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	//
	// Single XML Element Type-Cast Value Retrieval
	//
	
	public function helper( $elementName, $parent = null ) {
		$element = $this->element( $elementName, $parent );
		if( null === $element ) {
			return null;
		}
		
		$helper = new XmlHelper();
		$helper->setElement( $element )
				->withNamespaces( $this->_namespaces );
		
		return $helper;
	}
	
	public function string( $elementName, $parent = null ) {
		$element = $this->element( $elementName, $parent );
		if( null === $element ) {
			return null;
		}
		
		return (string)$element->nodeValue;
	}
	
	public function integer( $elementName, $parent = null ) {
		$element = $this->element( $elementName, $parent );
		if( null === $element ) {
			return null;
		}
		
		return intval( $element->nodeValue );
	}

	public function float( $elementName, $parent = null ) {
		$element = $this->element( $elementName, $parent );
		if( null === $element ) {
			return null;
		}
		
		return floatval( $element->nodeValue );
	}
	
	public function dateTime( $elementName, $format, $parent = null ) {
		$element = $this->element( $elementName, $parent );
		if( null === $element ) {
			return null;
		}
		
		return \DateTime::createFromFormat( $format, $element->nodeValue );
	}
	
	public function int( $elementName, $parent = null ) {
		return $this->integer( $elementName, $parent );
	}

	public function double( $elementName, $parent = null ) {
		return $this->float( $elementName, $parent );
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	//
	// Multi XML Element Type-Cast Value Retrieval
	//
	
	public function helpers( $xpath, $parent = null ) {
		return array_map(
			function( $element ) {
				$helper = new XmlHelper();
				$helper->setElement( $element )
						->withNamespaces( $this->_namespaces );
				return $helper;
			},
			$this->elements( $xpath, $parent )
		);
	}
	
	public function strings( $xpath, $parent = null ) {
		return array_map(
			function( $element ) { return (string)$element->nodeValue; },
			$this->elements( $xpath, $parent )
		);
	}
	
	public function integers( $xpath, $parent = null ) {
		return array_map(
			function( $element ) { return intval($element->nodeValue); },
			$this->elements( $xpath, $parent )
		);
	}

	public function floats( $xpath, $parent = null ) {
		return array_map(
			function( $element ) { return floatval($element->nodeValue); },
			$this->elements( $xpath, $parent )
		);
	}

	public function dateTimes( $xpath, $format, $parent = null ) {
		return array_map(
			function( $element ) { return \DateTime::createFromFormat( $format, $element->nodeValue); },
			$this->elements( $xpath, $parent )
		);
	}

	public function ints( $xpath, $parent = null ) {
		return $this->integers( $xpath, $parent );
	}

	public function doubles( $xpath, $parent = null ) {
		return $this->floats( $xpath, $parent );
	}
	
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	//
	// Single XML Attribute Type-Cast Value Retrieval
	//
	
	public function stringAttribute( $attributeName, $parent = null ) {
		$attribute = $this->attribute( $attributeName, $parent );
		if( null === $attribute ) {
			return null;
		}
		
		return (string)$attribute;
	}
	
	public function integerAttribute( $attributeName, $parent = null ) {
		$attribute = $this->attribute( $attributeName, $parent );
		if( null === $attribute ) {
			return null;
		}
		
		return intval( $attribute );
	}
	
	public function floatAttribute( $attributeName, $parent = null ) {
		$attribute = $this->attribute( $$attributeName, $parent );
		if( null === $attribute ) {
			return null;
		}
		
		return floatval( $attribute );
	}
	
	public function intAttribute( $attributeName, $parent = null ) {
		return $this->integerAttribute( $attributeName, $parent );
	}
	
	public function doubleAttribute( $attributeName, $parent = null ) {
		return $this->floatAttribute( $attributeName, $parent );
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	//
	// DOM Manipulation
	//
	
	public function add( $elementName, $value = null, $parent = null ) {
		$document = $this->_getDocument();
		if( null === $document ) {
			throw new \Exception( "Cannot add elements without a DOMDocument." );
		}
		
		$element = $document->createElement( $elementName, $value );
		if( null === $parent ) {
			$this->_rootElement->appendChild( $element );
		} else {
			$parent->appendChild( $element );
		}
		
		$helper = new XmlHelper();
		$helper->setElement( $element )
				->withNamespaces( $this->_namespaces );
		
		return $helper;
	}
	
	public function setAttribute( $attributeName, $attributeValue, $parent = null ) {
		if( null === $parent ) {
			$parent = $this->_rootElement;
		}
		$parent->setAttribute( $attributeName, $attributeValue );
	}
	
}