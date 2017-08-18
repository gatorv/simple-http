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
     * The active user agent
     * @var string
     */
    private $user_agent;
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
     * The number of redirects to follow or 0 to disable
     * @var int
     */
    private $redirects;
    /**
     * The Proxy to use
     * @var string
     */
    private $proxy;
    /**
     * The proxy type to use, can be one of:
     * - CURLPROXY_HTTP
     * - CURLPROXY_SOCKS4
     * - CURLPROXY_SOCKS5
     * - CURLPROXY_SOCKS4A
     * - CURLPROXY_SOCKS5_HOSTNAME
     * @var int
     */
    private $proxy_type;
    /**
     * Enables or disables the SSL Verification
     * @var boolean
     */
    private $verifySSL;

    /**
     * Creates a new SimpleHttpRequest object with the supplied options
     * 
     * @param array $options
     */
    public function __construct(array $options = array()) {
        $this->request_headers = [];
        $this->options = $options;

        $this->initCurl();
        $this->useDesktopAgent();
        $this->parseOptions();
    }

    /**
     * Switches the User-Agent to a Desktop Browser
     * @return void
     */
    public function useDesktopAgent() {
        $this->user_agent = $this->desktop_agent;
    }

    /**
     * Switches the User-Agent to a Mobile Browser
     * @return void
     */
    public function useMobileAgent() {
        $this->user_agent = $this->mobile_agent;
    }

    /**
     * Sets the user Agent to a custom string
     * @param String $agent The user Agent for the request
     */
    public function setUserAgent($agent) {
        $this->user_agent = $agent;
    }

    /**
     * Returns the active user agent
     * @return String
     */
    public function getUserAgent() {
        return $this->user_agent;
    }

    /**
     * Resets the request headers
     * @return void
     */
    public function resetHeaders() {
        $this->request_headers = [];
    }

    /**
     * Returns the Request Headers
     * @return array
     */
    public function getHeaders() {
        return $this->request_headers;
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
     * Follows a Location header $num times
     * @param $num The number of times to follow redirects
     * @return void
     */
    public function setFollowRedirects($num = 2) {
        $this->redirects = $num;
    }

    /**
     * Returns the number of redirects
     * @return int
     */
    public function getFollowRedirects() {
        return $this->redirects;
    }

    /**
     * Sets a HTTP Proxy
     * @param string $proxy The full proxy with port
     * @param int $type The type of proxy see @$proxy
     * @return void
     */
    public function setProxy($proxy, $type = CURLPROXY_HTTP) {
        $this->proxy = $proxy;
        $this->proxy_type = $type;
    }

    /**
     * Return the proxy and type
     * @return array The proxy and type
     */
    public function getProxy() {
        return [$this->proxy, $this->proxy_type];
    }

    /**
     * Enables or disables SSL Verify
     * @param boolean $verify To verify or not, default to true
     * @return void
     */
    public function setVerifySSL($verify = true) {
        $this->verifySSL = $verify;
    }

    /**
     * Returns the SSL Verification status
     * @return boolean
     */
    public function getVerifySSL() {
        return $this->verifySSL;
    }

    /**
     * Sets another not mapped option to cURL, see PHP Documentaion
     *
     * @codeCoverageIgnore
     * @param mixed $option
     * @param mixed $value 
     */
    public function setCurlOpt($option, $value) {
        curl_setopt($this->ch, $option, $value);
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
     * Starts the cURL resource
     *
     * @codeCoverageIgnore
     * @return void
     */
    protected function initCurl() {
        $this->ch = curl_init();

        // Default Settings
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

    /**
     * Parses the Options of this class
     * @return void
     */
    protected function parseOptions() {
        foreach ($this->options as $option => $value) {
            switch ($option) {
            case 'redirects': $this->setFollowRedirects($value);
                break;
            case 'proxy': $this->setProxy($value);
                break;
            case 'ssl': $this->setVerifySSL($value);
                break;
            case 'useragent': $this->setUserAgent($value);
                break;
            default: throw new \BadFunctionCallException('Unknown option: ' . $option);
            }
        }
    }

    /**
     * Private function to process the request
     * @return array with response
     */
    protected function makeRequest() {
        if ($this->redirects == 0) {
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
            curl_setopt($this->ch, CURLOPT_MAXREDIRS, 0);
        } else {
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($this->ch, CURLOPT_MAXREDIRS, $this->redirects);
        }

        if (!empty($this->proxy)) {
            curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy);
            curl_setopt($this->ch, CURLOPT_PROXYTYPE, $this->proxy_type);
        }

        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->user_agent);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request_headers);

        $headers = [];
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$headers) {
            $len = strlen($header);
            $headers[] = trim($header);
            return $len;
        });

        $body = curl_exec($this->ch);

        if (curl_errno($this->ch) === 0) {
            return [$headers, $body];
        }

        throw new \RuntimeException(curl_error($this->ch));
    }

    /**
     * Closes the cURL resource
     *
     * @codeCoverageIgnore
     * @return void
     */
    public function __destruct() {
        curl_close($this->ch);
    }
}