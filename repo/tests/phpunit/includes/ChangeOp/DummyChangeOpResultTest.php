<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use Wikibase\Repo\ChangeOp\DummyChangeOpResult;

/**
 * @covers \Wikibase\Repo\ChangeOp\DummyChangeOpResult
 *
 * @group Wikibase
 * @group ChangeOp
 */
class DummyChangeOpResultTest extends \PHPUnit\Framework\TestCase {

	public function testIsEntityChangeAlwaysReturnFalse() {
		$changeOpResult = new DummyChangeOpResult();

		$this->assertFalse( $changeOpResult->isEntityChanged() );
	}

}
