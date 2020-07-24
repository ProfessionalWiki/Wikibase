<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Formatters;

use Title;

interface ImageLinker {

	public function buildUrl( Title $title ): string;

}
