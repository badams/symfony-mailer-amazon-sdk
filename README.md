symfony-amazon-mailer-sdk
================================

[![CI Action](https://github.com/badams/symfony-mailer-amazon-sdk/workflows/continuous-integration/badge.svg)](https://github.com/badams/symfony-mailer-amazon-sdk/workflows/continuous-integration)
[![codecov](https://codecov.io/gh/badams/symfony-mailer-amazon-sdk/branch/master/graph/badge.svg)](https://codecov.io/gh/badams/symfony-mailer-amazon-sdk)

An SES transport built for the [symfony/mailer](https://github.com/symfony/mailer) package which implements support for the official [aws/aws-sdk-php](https://github.com/aws/aws-sdk-php) package.
This differs from the official [symfony/amazon-mailer](https://github.com/symfony/amazon-mailer) as it relies on the official amazon sdk for authentication, meaning support for instance 
based authentication on EC2 boxes will work out of the box.  

---
