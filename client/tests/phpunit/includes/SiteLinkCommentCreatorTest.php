<?php

namespace Wikibase\Test;

use Diff\Diff;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\Item;
use Wikibase\ItemChange;
use Wikibase\SiteLinkCommentCreator;

/**
 * @covers Wikibase\SiteLinkCommentCreator
 *
 * @since 0.5
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCommentCreatorTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider getEditCommentProvider
	 */
	public function testGetEditComment( Diff $siteLinkDiff, $action, $comment, $expected ) {
		$commentCreator = new SiteLinkCommentCreator( 'enwiki' );
		$comment = $commentCreator->getEditComment( $siteLinkDiff, $action, $comment );

		$this->assertEquals( $expected, $comment );
	}

	public function getEditCommentProvider() {
		$changes = array();

		$updates = $this->getUpdates();

		foreach( $updates as $update ) {
			$changes[] = array(
				$update[0],
				ItemChange::UPDATE,
				'wikibase-comment-update',
				$update[1]
			);
		}

		$changes[] = array(
			$this->getDeleteDiff(),
			ItemChange::REMOVE,
			'wikibase-comment-remove',
			array( 'message' => 'wikibase-comment-remove' )
		);

		$changes[] = array(
			$this->getRestoreDiff(),
			ItemChange::RESTORE,
			'wikibase-comment-restore',
			array( 'message' => 'wikibase-comment-restore' )
		);

		return $changes;
	}

	protected function getNewItem() {
		$item = Item::newEmpty();
		$item->setId( ItemId::newFromNumber( 1 ) );

		return $item;
	}

	protected function getConnectDiff() {
		$item = $this->getNewItem();
		$item2 = $item->copy();
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Japan' ) );

		$change = ItemChange::newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getUnlinkDiff() {
		$item = $this->getNewItem();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Japan' ) );

		$item2 = $item->copy();
		$item2->removeSiteLink( 'enwiki', 'Japan' );

		$change = ItemChange::newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getLinkChangeDiff() {
		$item = $this->getNewItem();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Japan' ) );

		$item2 = $item->copy();
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Tokyo' ) );

		$change = ItemChange::newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getAddLinkDiff() {
		$item = $this->getNewItem();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Japan' ) );

		$item2 = $item->copy();
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Japan' ) );

		$change = ItemChange::newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getAddMultipleLinksDiff() {
		$item = $this->getNewItem();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Japan' ) );

		$item2 = $item->copy();
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Japan' ) );
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'frwiki', 'Japan' ) );

		$change = ItemChange::newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getRemoveLinkDiff() {
		$item = $this->getNewItem();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Japan' ) );
		$item->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Japan' ) );

		$item2 = $item->copy();
		$item2->removeSiteLink( 'dewiki', 'Japan' );

		$change = ItemChange::newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getChangeLinkDiff() {
		$item = $this->getNewItem();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Japan' ) );
		$item->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Japan' ) );

		$item2 = $item->copy();
		$item2->addSimpleSiteLink( new SimpleSiteLink( 'dewiki', 'Tokyo' ) );

		$change = ItemChange::newFromUpdate( ItemChange::UPDATE, $item, $item2 );

		return $change->getSiteLinkDiff();
	}

	protected function getDeleteDiff() {
		$item = $this->getNewItem();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Japan' ) );

		$change = ItemChange::newFromUpdate( ItemChange::REMOVE, $item, null );

		return $change->getSiteLinkDiff();
	}

	protected function getRestoreDiff() {
		$item = $this->getNewItem();
		$item->addSimpleSiteLink( new SimpleSiteLink( 'enwiki', 'Japan' ) );

		$change = ItemChange::newFromUpdate( ItemChange::RESTORE, null, $item );

		return $change->getSiteLinkDiff();
	}

	protected function getUpdates() {
		$updates = array();

		$updates[] = array(
			$this->getConnectDiff(),
			array( 'message' => 'wikibase-comment-linked' )
		);

		$updates[] = array(
			$this->getUnlinkDiff(),
			array( 'message' => 'wikibase-comment-unlink' ),
		);

		$updates[] = array(
			$this->getLinkChangeDiff(),
			array(
				'message' => 'wikibase-comment-sitelink-change',
				'sitelink' => array(
					'oldlink' => array(
						'site' => 'enwiki',
						'page' => 'Japan'
					),
					'newlink' => array(
						'site' => 'enwiki',
						'page' => 'Tokyo'
					)
				)
			)
		);

		$updates[] = array(
			$this->getAddLinkDiff(),
			array(
				'message' => 'wikibase-comment-sitelink-add',
				'sitelink' => array(
					'newlink' => array(
						'site' => 'dewiki',
						'page' => 'Japan'
					)
				)
			)
		);

		$updates[] = array(
			$this->getAddMultipleLinksDiff(),
			array(
				'message' => 'wikibase-comment-update'
			)
		);

		$updates[] = array(
			$this->getRemoveLinkDiff(),
			array(
				'message' => 'wikibase-comment-sitelink-remove',
				'sitelink' => array(
					'oldlink' => array(
						'site' => 'dewiki',
						'page' => 'Japan'
					)
				)
			)
		);

		$updates[] = array(
			$this->getChangeLinkDiff(),
			array(
				'message' => 'wikibase-comment-sitelink-change',
				'sitelink' => array(
					'oldlink' => array(
						'site' => 'dewiki',
						'page' => 'Japan'
					),
					'newlink' => array(
						'site' => 'dewiki',
						'page' => 'Tokyo'
					)
				)
			)
		);

		return $updates;
	}

}
