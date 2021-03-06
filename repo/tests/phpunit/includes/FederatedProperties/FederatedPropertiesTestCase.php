<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties;

use MediaWikiIntegrationTestCase;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
abstract class FederatedPropertiesTestCase extends MediaWikiIntegrationTestCase {

	protected function setFederatedPropertiesEnabled() {
		$settings = WikibaseRepo::getDefaultInstance()->getSettings();
		$settings->setSetting( 'federatedPropertiesEnabled', true );
	}
}
