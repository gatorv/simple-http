<?php
namespace Gatorv\Web;

class SimpleHttpRequest {
    /**
     * Desktop Browser User-Agent
     * @var string
     */
    private $desktop_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7';
    /**
     * Mobile Browser User-Agent
     * @var string
     */
    private $mobile_agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25';
    /**
     * Request headers array to send
     * @var array
     */
    private $request_headers;
    /**
     * cURL resource
     * @var resource
     */
    private $ch;
    /**
     * The options array
     * @var array
     */
    private $options;

    /**
     * Creates a new SimpleHttpRequest object with the supplied options
     * 
     * @param array $options
     */
    public function __construct(array $options = array()) {
        $this->request_headers = [];
        $this->options = $options;

        $this->initCurl();
        $this->parseOptions();
        $this->useDesktopAgent();
    }

    /**
     * Switches the User-Agent to a Desktop Browser
     * @return void
     */
    public function useDesktopAgent() {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->desktop_agent);
    }

    /**
     * Switches the User-Agent to a Mobile Browser
     * @return void
     */
    public function useMobileAgent() {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->mobile_agent);
    }

    /**
     * Resets the request headers
     * @return void
     */
    public function resetHeaders() {
        $this->request_headers = [];
    }

    /**
     * Requests compression from the server
     * @return void
     */
    public function requestCompression() {
        $this->request_headers[] = 'Accept-Encoding: gzip,deflate';
    }

    /**
     * Adds cookies to the request
     * @param string|array $cookie A array or string of cookies
     * @return void
     */
    public function addCookie($cookie) {
        if (is_array($cookie)) {
            $cookie = implode(';', $cookie);
        }

        $this->request_headers[] = 'Cookie: ' . $cookie;
    }

    /**
     * Starts the cURL resource
     * @return void
     */
    private function initCurl() {
        $this->ch = curl_init();

        // Default Settings
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

    /**
     * Parses the Options of this class
     * @return void
     */
    private function parseOptions() {
        foreach ($this->options as $option => $value) {
            switch ($option) {
            case 'redirects':$this->followRedirects($value);
                break;
            case 'proxy':$this->setProxy($value);
                break;
            case 'ssl':$this->verifySSL($value);
                break;
            default:throw new \BadFunctionCallException('Unknown option: ' . $option);
            }
        }
    }

    /**
     * Follows a Location header $num times
     * @param $num The number of times to follow redirects
     * @return void
     */
    public function followRedirects($num = 2) {
        if ($num == 0) {
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($this->ch, CURLOPT_MAXREDIRS, 0);
        } else {
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($this->ch, CURLOPT_MAXREDIRS, $num);
        }
    }

    /**
     * Sets a HTTP Proxy
     * @param string $proxy The full proxy with port
     * @return void
     */
    public function setProxy($proxy) {
        curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    }

    /**
     * Enables or disables SSL Verify
     * @param boolean $verify To verify or not, default to true
     * @return void
     */
    public function verifySSL($verify = true) {
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $verify);
    }

    /**
     * Performs a HTTP Get request, returns a array with two elements
     * headers, and body
     * @param string $url The URL to get
     * @return array with response
     */
    public function get($url) {
        curl_setopt($this->ch, CURLOPT_URL, $url);

        return $this->makeRequest();
    }

    /**
     * Performs a HTTP Post request, retuns a array with two elements
     * headers and body
     * @param string $url The URL to post to
     * @param array $values The values to post
     * @return array with response
     */
    public function post($url, $values) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $values);

        return $this->makeRequest();
    }

    /**
     * Private function to process the request
     * @return array with response
     */
    private function makeRequest() {
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request_headers);
        $headers = [];
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
            $len = strlen($header);
            $headers[] = trim($header);
            return $len;
        });

        $body = curl_exec($this->ch);

        return [$headers, $body];
    }

    /**
     * Closes the cURL resource
     * @return void
     */
    public function __destruct() {
        curl_close($this->ch);
    }
}