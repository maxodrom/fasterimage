<?php
chdir(__DIR__);
include('../vendor/autoload.php');

/**
 * Class FasterImageTest
 */
class FasterImageTest extends \PHPUnit\Framework\TestCase
{
    use \phpmock\phpunit\PHPMock;

    /**
     * Set up.
     */
    protected function setUp() {
        parent::setUp();

        // Mock function_exists() to return false for all curl_multi_* functions when PHPUnit is invoked with DISABLE_CURL_MULTI=1 environment variable.
        if ( isset( $_ENV['DISABLE_CURL_MULTI'] ) && true === filter_var( $_ENV['DISABLE_CURL_MULTI'], FILTER_VALIDATE_BOOLEAN ) ) {
            $function_exists = $this->getFunctionMock('FasterImage', 'function_exists');
            $function_exists->expects($this->atLeastOnce())->willReturnCallback(
                function($function) {
                    if ( is_string($function) && 0 === strpos( $function, 'curl_multi_' ) ) {
                        return false;
                    } else {
                        return function_exists($function);
                    }
                }
            );
        }
    }

    /**
     * @throws Exception
     */
    public function test_invalid_images_return_failed()
    {
        $uris = [
            'http://example.com/foobarimage.jpg',
            'https://example.com/foobarimage.jpg',
            'sdfsdfdsfds',
        ];

        $client = new \FasterImage\FasterImage();
        $images = $client->batch($uris);

        foreach ( $images as $uri => $image ) {
            $this->assertArrayHasKey('size', $image);
            $this->assertEquals('failed', $image['size']);
        }
    }

    /**
     * @throws \Exception
     */
    public function test_batch_returns_size_and_type()
    {
        $data = $this->linksProvider();

        $expected = [];
        $uris     = [];

        foreach ( $data as $link ) {
            $uri    = current($link);
            $width  = next($link);
            $height = next($link);
            $type   = next($link);

            $uris[]         = $uri;
            $expected[$uri] = compact('width', 'height', 'type');
        }

        $client = new \FasterImage\FasterImage();
        $client->setTimeout(120);
        $images = $client->batch($uris);

        foreach ( $images as $uri => $image ) {
            $this->assertArrayHasKey('type', $image, "$uri is missing type: " . print_r($image, true));
            $this->assertEquals($expected[$uri]['type'], $image['type'], "Failed to get the right type for $uri");
            $this->assertArrayHasKey('size', $image, "There is no size defined for $uri " . print_r($image, true));
            $this->assertEquals($expected[$uri]['width'], $image['size'][0], "Failed to get the right width for $uri " . print_r($image, true));
            $this->assertEquals($expected[$uri]['height'], $image['size'][1], "Failed to get the right height for $uri " . print_r($image, true));
        }
    }

    /**
     * @return array
     */
    public function linksProvider()
    {
        return array(
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/exif_orientation.jpg', 600, 450, 'jpeg'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/favicon.ico', 16, 16, 'ico'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/infinite.jpg', 160, 240, 'jpeg'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/man.ico', 48, 48, 'ico'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/orient_2.jpg', 230, 408, 'jpeg'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test.bmp', 40, 27, 'bmp'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test.cur', 32, 32, 'cur'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test.gif', 17, 32, 'gif'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test.jpg', 882, 470, 'jpeg'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test.png', 30, 20, 'png'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test.psd', 17, 32, 'psd'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test.tiff', 85, 67, 'tiff'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test2.bmp', 1920, 1080, 'bmp'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test2.jpg', 250, 188, 'jpeg'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test2.tiff', 333, 225, 'tiff'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test3.jpg', 630, 367, 'jpeg'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/test4.jpg', 1485, 1299, 'jpeg'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/webp_vp8.webp', 550, 368, 'webp'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/webp_vp8l.webp', 386, 395, 'webp'],
            ['https://github.com/sdsykes/fastimage/raw/master/test/fixtures/webp_vp8x.webp', 386, 395, 'webp'],
            ['http://ketosizeme.com/wp-content/uploads/2016/11/Keto-Corn-Dog-Recipe-Low-Carb-High-Fat-.jpg', 700, 467, 'jpeg'],
            ['http://gluesticksgumdrops.com/wp-content/uploads/2015/03/how-to-find-more-time-to-read-to-your-kids.jpg', 700, 1000, 'jpeg'],
            ['https://s.w.org/images/core/emoji/11/svg/1f642.svg', 36, 36, 'svg'], // With viewBox only.
            ['https://gist.github.com/westonruter/0d66e6629526fc820a31fd43cf325376/raw/ed26b6d60b3d1c07a82907553795e6535c12da89/smiley-oval-vertical-padding.svg', 36, 96, 'svg'],
            ['https://gist.github.com/westonruter/0d66e6629526fc820a31fd43cf325376/raw/4c3b8d7fdad3233d669c05c7313c0503c8a3c8ed/smiley-with-dimensions-and-offcenter-viewbox.svg', 36, 42, 'svg'],
            ['https://gist.github.com/westonruter/0d66e6629526fc820a31fd43cf325376/raw/23651d8fec1a22707632f687b742700896daf54f/svg-tag-commented-out.svg', 38, 44, 'svg'],
        );
    }
}
