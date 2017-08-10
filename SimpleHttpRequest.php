<?php
namespace Gatorv\Web;

class SimpleHttpRequest {
    private $desktop_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.7) Gecko/20070914 Firefox/2.0.0.7';
    private $mobile_agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25';
    private $request_headers;
    private $ch;
    private $options;

    public function __construct(array $options = array()) {
        $this->request_headers = [];
        $this->options = $options;

        $this->initCurl();
        $this->setOptions();
        $this->usePCAgent();
    }

    public function useDesktopAgent() {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->desktop_agent);
    }

    public function useMobileAgent() {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->mobile_agent);
    }

    public function resetHeaders() {
        $this->request_headers = [];
    }

    public function requestCompression() {
        $this->request_headers[] = 'Accept-Encoding: gzip,deflate';
    }

    public function addCookie($cookie) {
        if (is_array($cookie)) {
            $cookie = implode(';', $cookie);
        }

        $this->request_headers[] = 'Cookie: ' . $cookie;
    }

    private function initCurl() {
        $this->ch = curl_init();

        // Default Settings
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    }

    private function parseOptions() {
        foreach ($this->options as $option => $value) {
            switch ($option) {
                case 'redirects': $this->followRedirects($value); break;
                case 'proxy': $this->setProxy($value); break;
                case 'ssl': $this->verifySSL($value); break;
            }
        }
    }

    public function followRedirects($num = 2) {
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->ch, CURLOPT_MAXREDIRS, $num);
    }

    public function setProxy($proxy) {
        curl_setopt($this->ch, CURLOPT_PROXY, $this->proxy);
        curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    }

    public function verifySSL($verify = true) {
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, $verify);
    }

    public function get($url) {
        curl_setopt($this->ch, CURLOPT_URL, $url);

        return $this->makeRequest();
    }

    public function post($values) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $values);

        return $this->makeRequest();
    }

    private function makeRequest() {
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->request_headers);
        $headers = [];
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$headers) { 
            $len = strlen($header);
            $headers[] = trim($header);
            return $len;
        });

        $body = curl_exec($this->ch);

        return [$headers, $body];
    }

    public function __destruct() {
        curl_close($this->ch);
    }
}