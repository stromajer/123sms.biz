# 123sms.biz API PHP CLIENT
Simple class for work with http://123sms.biz API

This is simple class which simplifies work with 123sms.biz SMS gateway.

It can:
-Send one / multiple SMS
-Get remaining credit information
-Verify telephone number

If you want more functionality from 123sms.biz API, feel free to message me <stromaler@gospace.sk>.

I implemented only most used functions what I need.

Example:



1. SEND SMS:
```PHP
$sms = new SmsManager();
$sms->setSender('Me')
    ->setCredentials('myUsername','password')
    ->setMessage('Hi, this is test SMS')
    ->addRecipient('00421111222')
    ->sendSms();
```
    
2. GET CREDIT INFORMATION:

```PHP
$sms = new SmsManager();
$myCredit = $sms->setCredentials('myUsername','password')>getCredit();
```

3. VERIFY NUMBER:

```PHP
$sms = new SmsManager();
$numberInfo = $sms->setCredentials('myUsername','password')>verifyNumber('00421111222');
```
The only dependency is CURL module in PHP.

Enjoy.
