<?php namespace Stromaler\Biz123Sms;
/**
 * PHP Class for http://123sms.biz SMS GATE API V 1.0
 * Uses CURL with REST version of API
 *
 * This is free software. Use it and modify as you like.
 * If you like it, send me tip ;) (BTC) : 1NkHCRUmYCGEKBAJUiKdqUJ3Nj3QNuTzmh
 * (c) 2015 Michal Stromajer <stromaler@gospace.sk>
 */

class SmsManager
{

    //response codes meanings
    const RESPONSE_SMS_ACCEPTED_AND_SHEDULED = 2,
        RESPONSE_SMS_ACCEPTED = 1,
        RESPONSE_SMS_ACCEPTED_LOW_CREDIT_WARN = 0,
        RESPONSE_SMS_INVALID_CREDENTIALS = -1,
        RESPONSE_SMS_INSUFFICIENT_CREDIT = -2,
        RESPONSE_SMS_INACTIVE_NUMBER = -3,
        RESPONSE_SMS_UNPAID_BILL_NUMBER_INACTIVE = -4,
        RESPONSE_SMS_INVALID_NUMBER = -5,
        RESPONSE_SMS_INVALID_SENDER = -6,
        RESPONSE_SMS_INVALID_TEXT = -7,
        RESPONSE_SMS_TOO_LONG_TEXT = -8,
        RESPONSE_SMS_HLR_INVALID_NUMBER = -9,
        RESPONSE_SMS_TIME_LIMIT_EXCEEDED = -10,
        RESPONSE_SMS_UNKNOWN_ERROR = -11;


    /**
     * url for posting sms
     */
    const SMS_API_URL = 'http://www.123sms.sk/api/rest/';

    /**
     * url to get credit information
     */
    const SMS_API_URL_CREDIT = 'http://www.123sms.sk/api/credit/';

    /**
     * url for verify number
     */
    const SMS_API_URL_VERIFY_NUMBER = 'http://www.123sms.sk/api/hlr/';

    /**
     * max sender name length
     */
    const MAX_SENDER_NAME_LENGTH = 11;

    /**
     * @var string username to 123sms.biz
     *
     */
    protected $username;

    /**
     * @var string password to 123sms.biz
     */
    protected $password;

    /**
     * @var string if you dont want use  your password, use API key from 123sms.biz
     */
    protected $apiKey;

    /**
     * @var bool is credentials set ?
     */
    protected $isAuthenticated = false;

    private $curl;

    private $curlConfig = array();

    /**
     * @var array sms recipients
     */
    private $recipients = array();

    /**
     * @var string sms message
     */
    protected $message;

    /**
     * @var string sender who sending sms
     */
    protected $sender;

    /**
     * for every sent sms will be set array with result received from API
     * @var array
     */
    protected $response;

    public function __construct()
    {
        if (!function_exists('curl_init')) {
            throw new \Exception('php-curl extension missing');
        }

        $this->curl = curl_init();

        $this->curlConfig = [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
        ];
    }

    /**
     * Adds number where is sms going to be send
     *
     * @param $number string|array
     * @return $this
     */
    public function addRecipient($number)
    {
        if (is_array($number)) {
            $mergedNumbers = array_merge($this->recipients, $number);
            $this->recipients = array_unique($mergedNumbers);

            return $this;
        }

        if (!in_array($number, $this->recipients)) {
            $this->recipients[] = $number;
        }

        return $this;
    }

    /**
     * Clears all added numbers
     *
     * @return $this
     */
    public function removeRecipients()
    {
        $this->recipients = array();

        return $this;
    }

    /**
     * Set auth credentials. You have two choices: username and password or username and API key
     *
     * @param null $username
     * @param null $password
     * @param null $apiKey
     * @return $this
     * @throws \Exception
     */
    public function setCredentials($username, $password = null, $apiKey = null)
    {
        if (!$password && !$apiKey) {
            throw new \Exception('Password or API key is mandatory');
        }

        if (!$username) {
            throw new \Exception('Username is mandatory.');
        }


        $this->username = $username;
        $this->password = $password;
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Checks if any authentication method was provided
     * e.g api key, or username, password
     *
     * @return bool
     * @throws \Exception
     */
    private function credentialsOk()
    {
        if ($this->username) {
            if ($this->password || $this->apiKey) {
                return true;
            }
            throw new \Exception('Set password or API key.');
        }
        throw new \Exception('Set username.');
    }

    /**
     * Checks if any recipient was set
     *
     * @throws \Exception if not
     * @return true if yes
     */
    private function hasRecipients()
    {
        if (count($this->recipients)) {
            return true;
        }
        throw new \Exception('No recipients was set.');
    }

    /**
     * Checks if any sender name was set
     *
     * @throws \Exception if not
     * @return true if yes
     */
    private function senderOk()
    {
        if ($this->getSender()) {
            return true;
        }

        throw new \Exception('No sender was set.');
    }

    /**
     * Checks if sms message was set
     *
     * @throws \Exception if not
     * @return true if yes
     */
    private function hasMessage()
    {
        if ($this->getSender()) {
            return true;
        }

        throw new \Exception('No message was set.');
    }


    /**
     * get amount of remaining credit
     *
     * @return \SimpleXMLElement
     */
    public function getCredit()
    {
        $this->credentialsOk();
        $this->curlConfig[CURLOPT_URL] = self::SMS_API_URL_CREDIT;
        $this->curlConfig[CURLOPT_POSTFIELDS]['username'] = $this->getUsername();
        $this->curlConfig[CURLOPT_POSTFIELDS]['password'] = $this->getPassword() ? $this->getPassword() : $this->getApiKey();
        curl_setopt_array($this->curl, $this->curlConfig);

        $xml = simplexml_load_string(curl_exec($this->curl));

        return $xml;
    }

    /**
     * verify mobile number
     *
     * @param string number
     * @return \SimpleXMLElement
     */
    public function verifyNumber($number)
    {
        $this->credentialsOk();
        $this->curlConfig[CURLOPT_URL] = self::SMS_API_URL_VERIFY_NUMBER;
        $this->curlConfig[CURLOPT_POSTFIELDS]['username'] = $this->getUsername();
        $this->curlConfig[CURLOPT_POSTFIELDS]['password'] = $this->getPassword() ? $this->getPassword() : $this->getApiKey();
        $this->curlConfig[CURLOPT_POSTFIELDS]['destination'] = $number;

        curl_setopt_array($this->curl, $this->curlConfig);

        $xml = simplexml_load_string(curl_exec($this->curl));

        return $xml;
    }

    /**
     * checks if everything ok and finally sends sms to all user(s)
     *
     * @return array - for each recipient will return XML response from api.
     * e.g: ['158' => {response object}, ...]
     */
    public function sendSms()
    {
        $this->credentialsOk();
        $this->senderOk();
        $this->hasRecipients();
        $this->hasMessage();
        $this->curlConfig[CURLOPT_POSTFIELDS] = array();
        $this->curlConfig[CURLOPT_URL] = self::SMS_API_URL;
        $this->curlConfig[CURLOPT_POSTFIELDS]['username'] = $this->getUsername();
        $this->curlConfig[CURLOPT_POSTFIELDS]['password'] = $this->getPassword() ? $this->getPassword() : $this->getApiKey();
        $this->curlConfig[CURLOPT_POSTFIELDS]['message'] = $this->getMessage();
        $this->curlConfig[CURLOPT_POSTFIELDS]['sender'] = $this->getSender();

        foreach ($this->getRecipients() as $recipient) {
            $this->curlConfig[CURLOPT_POSTFIELDS]['recipient'] = $recipient;
            curl_setopt_array($this->curl, $this->curlConfig);
            $this->response[$recipient] = simplexml_load_string(curl_exec($this->curl));
        }

        return $this->response;
    }

    /**
     * Set sender of sms.
     * methods checks if name is not too long
     *
     * @param mixed $sender
     * @return $this
     * @throws \Exception
     */
    public function setSender($sender)
    {
        if (strlen($sender) <= self::MAX_SENDER_NAME_LENGTH) {
            $this->sender = $sender;
            return $this;
        }

        throw new \Exception('Sender name exceeds maximum length ( ' . self::MAX_SENDER_NAME_LENGTH . ' )');
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return array
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * set message what will be in sms
     *
     * @param mixed $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSender()
    {
        return $this->sender;
    }

    public function getResponseMeanings()
    {
        return [
            self::RESPONSE_SMS_ACCEPTED_AND_SHEDULED,
            self::RESPONSE_SMS_ACCEPTED,
            self::RESPONSE_SMS_ACCEPTED_LOW_CREDIT_WARN,
            self::RESPONSE_SMS_INVALID_CREDENTIALS,
            self::RESPONSE_SMS_INSUFFICIENT_CREDIT,
            self::RESPONSE_SMS_INACTIVE_NUMBER,
            self::RESPONSE_SMS_UNPAID_BILL_NUMBER_INACTIVE,
            self::RESPONSE_SMS_INVALID_NUMBER,
            self::RESPONSE_SMS_INVALID_SENDER,
            self::RESPONSE_SMS_INVALID_TEXT,
            self::RESPONSE_SMS_TOO_LONG_TEXT,
            self::RESPONSE_SMS_HLR_INVALID_NUMBER,
            self::RESPONSE_SMS_TIME_LIMIT_EXCEEDED,
            self::RESPONSE_SMS_UNKNOWN_ERROR,
        ];
    }
}
