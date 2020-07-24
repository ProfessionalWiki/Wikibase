<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Lib\Formatters\CommonsImageLinker;
use Wikibase\Lib\Formatters\ImageLinkFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\ImageLinkFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class ImageLinkFormatterTest extends \MediaWikiTestCase {

	public function commonsLinkFormatProvider() {
		return [
			[
				new StringValue( 'example.jpg' ), // Lower-case file name
				'@<a .*href="https://commons.wikimedia.org/wiki/File:Example.jpg".*>.*example.jpg.*</a>@'
			],
			[
				new StringValue( 'Example.jpg' ),
				'@<a .*href="https://commons.wikimedia.org/wiki/File:Example.jpg".*>.*Example.jpg.*</a>@'
			],
			[
				new StringValue( 'Example space.jpg' ),
				'@<a .*href="https://commons.wikimedia.org/wiki/File:Example_space.jpg".*>.*Example space.jpg.*</a>@'
			],
			[
				new StringValue( 'Example_underscore.jpg' ),
				'@<a .*href="https://commons.wikimedia.org/wiki/File:Example_underscore.jpg".*>.*Example_underscore.jpg.*</a>@'
			],
			[
				new StringValue( 'Example+plus.jpg' ),
				'@<a .*href="https://commons.wikimedia.org/wiki/File:Example%2Bplus.jpg".*>.*Example\+plus.jpg.*</a>@'
			],
			[
				new StringValue( '[[File:Invalid_title.mid]]' ),
				'@^\[\[File:Invalid_title.mid\]\]$@'
			],
			[
				new StringValue( '<a onmouseover=alert(0xF000)>ouch</a>' ),
				'@^&lt;a onmouseover=alert\(0xF000\)&gt;ouch&lt;/a&gt;$@'
			],
			[
				new StringValue( '' ),
				'@^$@'
			],
		];
	}

	/**
	 * @dataProvider commonsLinkFormatProvider
	 */
	public function testFormat( StringValue $value, $pattern ) {
		$formatter = new ImageLinkFormatter( new CommonsImageLinker(), '' );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function testFormatError() {
		$formatter = new ImageLinkFormatter( new CommonsImageLinker(), '' );
		$value = new NumberValue( 23 );

		$this->expectException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

	public function testCssClass() {
		$formatter = new ImageLinkFormatter( new CommonsImageLinker(), 'kittens' );

		$this->assertStringContainsString(
			'class="kittens"',
			$formatter->format( new StringValue( 'MyImage.png' ) )
		);
	}

}
