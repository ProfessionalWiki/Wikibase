<?php

namespace Wikibase\Repo\Rdf\Values;

use DataValues\DataValue;
use TitleFactory;

/**
 * RDF mapping for local media values.
 *
 * @license GPL-2.0-or-later
 */
class LocalMediaRdfBuilder extends ObjectUriRdfBuilder {

	private $titleFactory;

	public function __construct( TitleFactory $titleFactory ) {
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param DataValue $value
	 *
	 * @return string the object URI
	 */
	protected function getValueUri( DataValue $value ) {
		return $this->titleFactory->newFromText( $value->getValue(), NS_FILE )->getFullURL();
	}

}
