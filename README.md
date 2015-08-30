# 123sms.biz API PHP CLIENT
Simple class for work with http://123sms.biz (http://123sms.sk) API

This class fasten work with 123sms.biz SMS gateway.

It can:
-- Send one / multiple SMS
-- Get remaining credit information
-- Verify telephone number

If you want more functionality from 123sms.biz API, feel free to message me <stromaler@gospace.sk>.

I implemented only most used functions what I need.

Example:



 SEND SMS:
```PHP
$sms = new Stromaler\Biz123Sms\SmsManager();
$sms->setSender('Me')
    ->setCredentials('myUsername','password')
    ->setMessage('Hi, this is test SMS')
    ->addRecipient('00421111222')
    ->sendSms();
```
    
 GET CREDIT INFORMATION:

```PHP
$sms = new Stromaler\Biz123Sms\SmsManager();
$myCredit = $sms->setCredentials('myUsername','password')->getCredit();
```

 VERIFY NUMBER:

```PHP
$sms = new Stromaler\Biz123Sms\SmsManager();
$numberInfo = $sms->setCredentials('myUsername','password')->verifyNumber('00421111222');
```
The only dependency is CURL module in PHP.

Enjoy.
