Symfony Mailer Amazon SDK Transport
================================

[![CI Action](https://github.com/badams/symfony-mailer-amazon-sdk/workflows/continuous-integration/badge.svg)](https://github.com/badams/symfony-mailer-amazon-sdk/workflows/continuous-integration)
[![codecov](https://codecov.io/gh/badams/symfony-mailer-amazon-sdk/branch/master/graph/badge.svg)](https://codecov.io/gh/badams/symfony-mailer-amazon-sdk)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/badams/symfony-mailer-amazon-sdk/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/badams/symfony-mailer-amazon-sdk/?branch=master)

An SES transport built for the [symfony/mailer](https://github.com/symfony/mailer) package which implements support for the official [aws/aws-sdk-php](https://github.com/aws/aws-sdk-php) package.
This differs from the official [symfony/amazon-mailer](https://github.com/symfony/amazon-mailer) as it relies on the official amazon sdk for authentication, meaning support for instance 
based authentication on EC2 boxes will work out of the box.  

Getting Started
--------------

Read the [documentation](https://symfony.com/doc/current/components/mailer.html) for the symfony/mailer package.

The transport should be installed using composer. 

```bash
 composer require badams/symfony-mailer-amazon-sdk
```

Below is an example of manually configuring the mailer component to use this transport
```php
use Badams\AmazonMailerSdk;

$factory = new Symfony\Component\Mailer\Transport([
     new SesSdkTransportFactory()
]);

$transport = $factory->fromString('ses+sdk://ap-south-2?credentials=env');
$mailer =  new \Symfony\Component\Mailer\Mailer($transport);

$mailer->send($email);
```

Configuration
------------

This transport supports configuration via DSN, below are example DSNs demonstrating how to configure the supported credential providers.

| Authentication        | Example DSN | Docs |
|-----------------------|-------------------------------------------|-----------------------------------------------------------------------------------------------------------------------|
| Default Provider      | ses+sdk://eu-east-1                       | [Link](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.Credentials.CredentialProvider.html#_defaultProvider) |
| Static Credentials    | ses+sdk://ACCESS_KEY:SECRET_KEY@eu-west-1 | [Link](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.Credentials.CredentialProvider.html#_fromCredentials) |
| Environment Variables | ses+sdk://eu-west-1?credentials=env       | [Link](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.Credentials.CredentialProvider.html#_env)             |
| Instance Profile      | ses+sdk://ap-south-2?credentials=instance | [Link](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.Credentials.CredentialProvider.html#_instanceProfile) |
| ECS                   | ses+sdk://us-east-1?credentials=ecs       | [Link](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.Credentials.CredentialProvider.html#_instanceProfile) |
