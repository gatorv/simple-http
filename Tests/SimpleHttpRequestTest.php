<?php
namespace Gatorv\Web\Tests;

use Gatorv\Web\SimpleHttpRequest;
use PHPUnit\Framework\TestCase;

class SimpleHttpRequestTest extends TestCase {

    public function testDefaultOptions() {
        $req = new SimpleHttpRequest();

        // Test the default UA is Desktop UA
        $desktop_ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7';
        $ua = $req->getUserAgent();

        $this->assertEquals($ua, $desktop_ua);
    }

    public function testConstructorOptions() {
        $proxy = '127.0.0.1:8000';
        $redirects = 5;
        $ua = 'foobar';
        $ssl = false;

        $options = [
            'redirects' => $redirects,
            'proxy'     => $proxy,
            'ssl'       => $ssl,
            'useragent' => $ua
        ];

        $req = new SimpleHttpRequest($options);

        $this->assertEquals($redirects, $req->getFollowRedirects());
        $this->assertEquals([$proxy, CURLPROXY_HTTP], $req->getProxy());
        $this->assertEquals($ua, $req->getUserAgent());
        $this->assertEquals($ssl, $req->getVerifySSL());
    }

    /**
     * @expectedException BadFunctionCallException
     */
    public function testBadConstructorOptions() {
        $options = [
            'bad' => 'bad'
        ];

        $req = new SimpleHttpRequest($options);
    }

    public function testMobileUA() {
        $req = new SimpleHttpRequest();

        // Mobile UA
        $mobile_ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25';
        $req->useMobileAgent();
        $ua = $req->getUserAgent();

        $this->assertEquals($ua, $mobile_ua);
    }

    public function testCustomUA() {
        $req = new SimpleHttpRequest();

        $requested_ua = 'foo';
        $req->setUserAgent($requested_ua);
        $ua = $req->getUserAgent();

        $this->assertEquals($ua, $requested_ua);
    }

    public function testResetHeaders() {
        $req = new SimpleHttpRequest();

        $req->requestCompression();
        $req->resetHeaders();

        $headers = $req->getHeaders();

        $this->assertCount(0, $headers);
    }

    public function testHeaderCompression() {
        $req = new SimpleHttpRequest();

        $req->requestCompression();
        $headers = $req->getHeaders();

        $compressionHeader = 'Accept-Encoding: gzip,deflate';
        $this->assertContains($compressionHeader, $headers);
    }

    public function testHeaderCookieString() {
        $req = new SimpleHttpRequest();

        $cookie = 'foo=bar';
        $req->addCookie($cookie);
        $expect = 'Cookie: ' . $cookie;

        $headers = $req->getHeaders();
        $this->assertContains($expect, $headers);
    }

    public function testHeaderCookieArray() {
        $req = new SimpleHttpRequest();

        $cookies = ['foo=bar', 'baz=faa', 'maa=mee'];
        $req->addCookie($cookies);
        $expect = 'Cookie: foo=bar;baz=faa;maa=mee';

        $headers = $req->getHeaders();
        $this->assertContains($expect, $headers);
    }

    public function testFollowRedirects() {
        $req = new SimpleHttpRequest();

        $num = 5;
        $req->setFollowRedirects($num);

        $this->assertEquals($req->getFollowRedirects(), $num);
    }

    public function testSetProxy() {
        $req = new SimpleHttpRequest();

        $proxy = '127.0.0.1:8000';
        $req->setProxy($proxy);

        $proxy_array = $req->getProxy();

        $this->assertEquals($proxy, $proxy_array[0]);
        $this->assertEquals(CURLPROXY_HTTP, $proxy_array[1]);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testBadGet() {
        $req = new SimpleHttpRequest();
        $response = $req->get('http://foobar');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testBadPost() {
        $req = new SimpleHttpRequest();
        $response = $req->post('http://foobar', []);
    }

    public function testMockGet() {
        $req = $this->getMockBuilder('Gatorv\Web\SimpleHttpRequest')
               ->setMethods(['makeRequest'])
               ->getMock();

        $expect = [['header1', 'header2'], '<html><body>test</body></html>'];

        $req->expects($this->once())
            ->method('makeRequest')
            ->will($this->returnValue($expect));

        $response = $req->get('http://foobar');

        $this->assertEquals($response, $expect);
    }

    public function testMockPost() {
        $req = $this->getMockBuilder('Gatorv\Web\SimpleHttpRequest')
               ->setMethods(['makeRequest'])
               ->getMock();

        $expect = [['header1', 'header2'], '<html><body>test</body></html>'];

        $req->expects($this->once())
            ->method('makeRequest')
            ->will($this->returnValue($expect));

        $response = $req->post('http://foobar', []);

        $this->assertEquals($response, $expect);
    }
}