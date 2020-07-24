<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use Title;

class CommonsImageLinker implements ImageLinker {

	public function buildUrl( Title $title ): string {
		return 'https://commons.wikimedia.org/wiki/File:' . $title->getPartialURL();
	}

}
