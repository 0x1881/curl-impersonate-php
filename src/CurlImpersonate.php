<?php
namespace CurlImpersonate;
class CurlImpersonate {
    private $engineCurl = "curl"; 
    private $url;
    private $method = 'GET';
    private $headers = array();
    private $cookieFile;
    private $cookieJar;
    private $data;
    private $includeHeaders = false; 
    private $sslVerifyPeer = true; 
    private $sslVerifyHost = true; 
    private $verbose = false; 
    private $handle;

    public function setopt($option, $value) {
        switch ($option) {
            case CURL_URL:
                $this->url = $value;
                break;
            case CURL_METHOD:
                $this->method = strtoupper($value);
                break;
            case CURL_POSTFIELDS:
                $this->data = $value;
                break;
            case CURL_HEADERS:
                $this->headers = array_merge($this->headers, $value);
                break;
            case CURL_INCLUDE_HEADERS:
                $this->includeHeaders = (bool)$value;
                break;
            case CURL_ENGINE:
                $this->engineCurl = $value;
                break;
            case CURL_COOKIEFILE:
                $this->cookieFile = $value;
                break;
            case CURL_COOKIEJAR:
                $this->cookieJar = $value;
                break;
            case CURL_SSL_VERIFYPEER:
                $this->sslVerifyPeer = $value;
                break;
            case CURL_SSL_VERIFYHOST:
                $this->sslVerifyHost = $value;
                break;
            case CURL_VERBOSE:
                $this->verbose = $value;
                break;
            default:
                throw new \InvalidArgumentException("Invalid option: {$option}");
        }
    }

    private function prepareData() {
        if (is_array($this->data) || is_object($this->data)) {
            $this->data = json_encode($this->data);
        }
    }

    public function exec() {
        $this->prepareData();

        $curlCommand = $this->engineCurl;
        $curlCommand .= ' -X ' . escapeshellarg($this->method);

        if ($this->cookieFile !== null) {
            $curlCommand .= ' --cookie ' . escapeshellarg($this->cookieFile);
        }

        if ($this->cookieJar !== null) {
            $curlCommand .= ' --cookie-jar ' . escapeshellarg($this->cookieJar);
        }

        if ($this->data !== null) {
            $curlCommand .= ' -d ' . escapeshellarg($this->data);
        }

        foreach ($this->headers as $header) {
            $curlCommand .= ' -H ' . escapeshellarg($header);
        }

        if ($this->includeHeaders) {
            $curlCommand .= ' -i';
        }

        if (!$this->sslVerifyPeer || !$this->sslVerifyHost) {
            $curlCommand .= ' -k';
        }

        if ($this->verbose) {
            $curlCommand .= ' -v';
        }

        $curlCommand .= ' ' . escapeshellarg($this->url);

        return $curlCommand;
    }

    public function execStandard($output = null) {
        $command = $this->exec();
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            proc_close($process);
        }

        return $output;
    }

    public function execStream() {
        $this->prepareData();

        $command = $this->exec();
        $this->handle = popen($command, 'r');
    }

    public function readStream($chunkSize = 4096) {
        if ($this->handle) {
            $output = fread($this->handle, $chunkSize);
            if ($output === false || feof($this->handle)) {
                $this->closeStream();
            }
            return $output;
        }
        return false;
    }

    public function closeStream() {
        if ($this->handle) {
            pclose($this->handle);
            $this->handle = null;
        }
    }
}


define('CURL_URL', 1);
define('CURL_METHOD', 2);
define('CURL_POSTFIELDS', 3);
define('CURL_HEADERS', 4);
define('CURL_INCLUDE_HEADERS', 5);
define('CURL_ENGINE', 6);
define('CURL_COOKIEFILE', 7);
define('CURL_COOKIEJAR', 8);
define('CURL_SSL_VERIFYHOST', 9);
define('CURL_SSL_VERIFYPEER', 10);
define('CURL_VERBOSE', 11);
