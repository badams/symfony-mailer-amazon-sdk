<?php

/*
 * This file is part of the badams/symfony-amazon-sdk-mailer package.
 *
 * (c) Byron Adams <byron.adams54@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Badams\AmazonMailerSdk;

use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class SesSdkTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        if (!$this->supports($dsn)) {
            throw new UnsupportedSchemeException($dsn);
        }

        $config = new SesSdkTransportConfig($this->getCredentials($dsn), $dsn->getHost(), [
            'ConfigurationSetName' => $dsn->getOption('ConfigurationSetName'),
        ]);

        return new SesSdkTransport($config, $this->dispatcher, $this->logger);
    }

    protected function getCredentials(Dsn $dsn): callable
    {
        $user = $dsn->getUser();
        $password = $dsn->getPassword();
        $credentialType = $dsn->getOption('credentials');

        if (null !== $user || null !== $password) {
            return CredentialProvider::fromCredentials(new Credentials($user, $password));
        }

        switch ($credentialType) {
            case 'env':
                return CredentialProvider::env();
            case 'instance':
                return CredentialProvider::instanceProfile();
            case 'ecs':
                return CredentialProvider::ecsCredentials();
        }

        return CredentialProvider::defaultProvider();
    }

    protected function getSupportedSchemes(): array
    {
        return ['ses+sdk'];
    }
}
