<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use Title;

class LocalImageLinker implements ImageLinker {

	public function buildUrl( Title $title ): string {
		return $title->getFullURL();
	}

}
