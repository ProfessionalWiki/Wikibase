<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Formatters;

use MediaWikiCoversValidator;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Formatters\LocalImageLinker;

/**
 * @covers \Wikibase\Lib\Formatters\LocalImageLinker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LocalImageLinkerTest extends TestCase {
	use MediaWikiCoversValidator;

	public function testBuildUrlReturnsFullUrl() {
		$title = \Title::newFromText( 'MyPage' );

		$this->assertSame(
			$title->getFullURL(),
			( new LocalImageLinker() )->buildUrl( $title )
		);
	}

}
