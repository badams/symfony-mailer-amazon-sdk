<?php

/*
 * This file is part of the badams/symfony-amazon-sdk-mailer package.
 *
 * (c) Byron Adams <byron.adams54@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Badams\AmazonMailerSdk\Transport;

use Aws\Credentials\Credentials;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Aws\Credentials\CredentialProvider;

final class SesSdkTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $region = $dsn->getOption('region');
        return new SesSdkTransport(self::getCredentials($dsn), $region);
    }

    protected static function getCredentials(Dsn $dsn): callable
    {
        $user = $dsn->getUser();
        $password = $dsn->getPassword();
        $credentialType = $dsn->getOption('credentials');

        if (null !== $user && null !== $password) {
            return CredentialProvider::fromCredentials(new Credentials($user, $password));
        }

        if ('env' === $credentialType) {
            return CredentialProvider::env();
        }

        if ('instance' === $credentialType) {
            return CredentialProvider::instanceProfile();
        }

        if ('ecs' === $credentialType) {
            return CredentialProvider::ecsCredentials();
        }

        return CredentialProvider::defaultProvider();
    }

    protected function getSupportedSchemes(): array
    {
        return ['ses+sdk'];
    }
}
