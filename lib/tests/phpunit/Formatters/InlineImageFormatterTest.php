<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use ParserOptions;
use Wikibase\Lib\Formatters\CommonsImageLinker;
use Wikibase\Lib\Formatters\InlineImageFormatter;
use Wikibase\Lib\Formatters\LocalImageLinker;

/**
 * @covers \Wikibase\Lib\Formatters\InlineImageFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Marius Hoch
 */
class InlineImageFormatterTest extends MediaWikiIntegrationTestCase {

	public function commonsInlineImageFormatterProvider() {
		$fileUrl = '.*//upload\.wikimedia\.org/wikipedia/commons/.*/120px-Example\.jpg';
		$pageUrl = 'https://commons\.wikimedia\.org/wiki/File:Example\.jpg';
		$exampleJpgHtmlRegex = '@<div .*<a[^>]+href="' . $pageUrl . '"[^>]*>' .
				'<img.*src="' . $fileUrl . '".*/></a></div>.*' .
				'<div .*><a[^>]+href="' . $pageUrl . '"[^>]*>Example\.jpg</a>.*\d+.*</div>@s';

		return [
			[
				new StringValue( 'example.jpg' ), // Lower-case file name
				$exampleJpgHtmlRegex
			],
			[
				new StringValue( 'Example.jpg' ),
				$exampleJpgHtmlRegex
			],
			[
				new StringValue( 'Example-That-Does-Not-Exist.jpg' ),
				'@^.*<a[^>]+href="https://commons.wikimedia.org/wiki/File:Example-That-Does-Not-Exist.jpg"[^>]*>@s'
			],
			[
				new StringValue( 'Dangerous-quotes""' ),
				'@/""/@s',
				false
			],
			[
				new StringValue( '<eviltag>' ),
				'@/<eviltag>/@s',
				false
			],
		];
	}

	/**
	 * @dataProvider commonsInlineImageFormatterProvider
	 */
	public function testFormat( StringValue $value, $pattern, $shouldContain = true ) {
		if ( $shouldContain &&
			!MediaWikiServices::getInstance()->getRepoGroup()->findFile( 'Example.jpg' )
		) {
			$this->markTestSkipped( '"Example.jpg" not found? Instant commons disabled?' );
		}

		$formatter = $this->newFormatter();

		$html = $formatter->format( $value );
		if ( $shouldContain ) {
			$this->assertRegExp( $pattern, $html );
		} else {
			$this->assertNotRegExp( $pattern, $html );
		}
	}

	public function testFormatError() {
		$formatter = $this->newFormatter();
		$value = new NumberValue( 23 );

		$this->expectException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

	public function testFormat_whenThumbsizeNotAvailable_usesFallback() {
		$formatter = $this->newFormatter( 1, [ 0 => 120 ] );
		$html = $formatter->format( new StringValue( 'example.jpg' ) );

		// fallback to using CommonsInLineImageFormatter::FALLBACK_THUMBNAIL_WIDTH
		$this->assertRegExp( '/320px-Example\.jpg/', $html );
	}

	private function newFormatter(
		$thumbSize = 0,
		$thumbLimits = [ 120 ],
		$captionCssClass = 'commons-media-caption'
	): InlineImageFormatter {
		if ( !MediaWikiServices::getInstance()->getRepoGroup()->findFile( 'Example.jpg' ) ) {
			$this->markTestSkipped( '"Example.jpg" not found? Instant commons disabled?' );
		}

		$parserOptions = ParserOptions::newFromAnon();
		$parserOptions->setThumbSize( $thumbSize );

		return new InlineImageFormatter(
			$parserOptions,
			$thumbLimits,
			'en',
			new CommonsImageLinker(),
			$captionCssClass
		);
	}

	public function testCaptionCssClass() {
		$formatter = $this->newFormatterWithCaptionCssClass( 'fluffy-kittens' );

		$this->assertStringContainsString(
			'class="fluffy-kittens"',
			$formatter->format( new StringValue( 'Whatever.jpg' ) )
		);
	}

	private function newFormatterWithCaptionCssClass( string $class ): InlineImageFormatter {
		$parserOptions = ParserOptions::newFromAnon();
		$parserOptions->setThumbSize( 0 );

		return new InlineImageFormatter(
			$parserOptions,
			[ 120 ],
			'en',
			new LocalImageLinker(),
			$class
		);
	}

}
