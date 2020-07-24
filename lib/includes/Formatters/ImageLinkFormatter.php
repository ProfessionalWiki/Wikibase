<?php

namespace Wikibase\Lib\Formatters;

use DataValues\StringValue;
use Html;
use InvalidArgumentException;
use Title;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * Formats the StringValue from "localMedia" and "commonsMedia" snaks as an HTML link
 * pointing to the file description page on the local wiki or Wikimedia Commons.
 *
 * @todo Use MediaWiki renderer
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class ImageLinkFormatter implements ValueFormatter {

	private $imageLinker;
	private $cssClass;

	public function __construct( ImageLinker $imageLinker, string $cssClass ) {
		$this->imageLinker = $imageLinker;
		$this->cssClass = $cssClass;
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * Formats the given commons file name as an HTML link
	 *
	 * @param StringValue $value The commons file name to turn into a link
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected a StringValue.' );
		}

		$fileName = $value->getValue();
		// We are using NS_MAIN only because makeTitleSafe requires a valid namespace
		// We cannot use makeTitle because it does not secureAndSplit()
		$title = Title::makeTitleSafe( NS_MAIN, $fileName );
		if ( $title === null ) {
			return htmlspecialchars( $fileName );
		}

		return Html::element(
			'a',
			[
				'class' => $this->cssClass,
				'href' => $this->imageLinker->buildUrl( $title )
			],
			$fileName
		);
	}

}
