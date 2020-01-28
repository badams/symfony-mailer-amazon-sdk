<?php

/*
 * This file is part of the badams/symfony-amazon-sdk-mailer package.
 *
 * (c) Byron Adams <byron.adams54@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Aws\Credentials\Credentials;
use Badams\AmazonMailerSdk\Transport\SesSdkTransport;
use Badams\AmazonMailerSdk\Transport\SesSdkTransportFactory;
use GuzzleHttp\Promise\Promise;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SesSdkTransportFactoryTest extends TestCase
{
    protected const USER = 'USER';
    protected const PASSWORD = 'PASS';
    protected const ENV_USER = 'ENV_USER';
    protected const ENV_PASSWORD = 'ENV_PASS';
    protected const INSTANCE_USER = 'INSTANCE_USER';
    protected const INSTANCE_PASSWORD = 'INSTANCE_PASS';
    protected const ECS_USER = 'ECS_USER';
    protected const ECS_PASSWORD = 'ECS_USER';
    protected const DEFAULT_USER = 'DEFAULT_USER';
    protected const DEFAULT_PASSWORD = 'DEFAULT_PASS';

    public function getFactory(): TransportFactoryInterface
    {
        return new SesSdkTransportFactory($this->getDispatcher(), $this->getClient(), $this->getLogger());
    }

    /**
     * @dataProvider createProvider
     * @param Dsn $dsn
     * @param TransportInterface $transport
     */
    public function testCreate(Dsn $dsn, TransportInterface $transport): void
    {
        $credentialsProviderMock = \Mockery::mock('overload:\Aws\Credentials\CredentialProvider');
        $credentialsProviderMock->allows([
            'fromCredentials' => $this->createCredentialsResolver(self::USER, self::PASSWORD),
            'env' => $this->createCredentialsResolver(self::ENV_USER, self::ENV_PASSWORD),
            'instanceProfile' => $this->createCredentialsResolver(self::INSTANCE_USER, self::INSTANCE_PASSWORD),
            'ecsCredentials' => $this->createCredentialsResolver(self::ECS_USER, self::ECS_PASSWORD),
            'defaultProvider' => $this->createCredentialsResolver(self::DEFAULT_USER, self::DEFAULT_PASSWORD),
        ]);

        $factory = $this->getFactory();
        $instance = $factory->create($dsn);
        $this->assertEquals($transport, $instance);
        $this->assertEquals((string)$transport, (string)$instance);
        $this->assertStringMatchesFormat($dsn->getScheme() . '://%S' . $dsn->getHost() . '%S', (string)$transport);

        \Mockery::close();
    }


    /**
     * @dataProvider supportsProvider
     */
    public function testSupports(Dsn $dsn, bool $supports): void
    {
        $factory = $this->getFactory();

        $this->assertSame($supports, $factory->supports($dsn));
    }

    /**
     * @dataProvider unsupportedSchemeProvider
     */
    public function testUnsupportedSchemeException(Dsn $dsn, string $message = null): void
    {
        $factory = $this->getFactory();

        $this->expectException(UnsupportedSchemeException::class);
        if (null !== $message) {
            $this->expectExceptionMessage($message);
        }

        $factory->create($dsn);
    }


    public function supportsProvider(): iterable
    {
        yield [
            new Dsn('ses+sdk', 'default'),
            true,
        ];

        yield [
            new Dsn('ses+https', 'default'),
            false,
        ];

        yield [
            new Dsn('ses+api', 'default'),
            false,
        ];

        yield [
            new Dsn('ses+smtp', 'default'),
            false,
        ];
    }

    public function createProvider(): iterable
    {
        $dispatcher = $this->getDispatcher();
        $logger = $this->getLogger();

        yield [
            new Dsn('ses+sdk', 'default', self::USER, self::PASSWORD, null, ['region' => 'us-west-1']),
            new SesSdkTransport(
                $this->createCredentialsResolver(self::USER, self::PASSWORD),
                'us-west-1',
                $dispatcher,
                $logger
            ),
        ];

        yield [
            new Dsn('ses+sdk', 'default', null, null, null, [
                'region' => 'ap-south-2',
                'credentials' => 'env',
            ]),
            new SesSdkTransport(
                $this->createCredentialsResolver(self::ENV_USER, self::ENV_PASSWORD),
                'ap-south-2',
                $dispatcher,
                $logger
            ),
        ];

        yield [
            new Dsn('ses+sdk', 'default', null, null, null, [
                'region' => 'ap-south-2',
                'credentials' => 'instance',
            ]),
            new SesSdkTransport(
                $this->createCredentialsResolver(self::INSTANCE_USER, self::INSTANCE_PASSWORD),
                'ap-south-2',
                $dispatcher,
                $logger
            ),
        ];

        yield [
            new Dsn('ses+sdk', 'default', null, null, null, ['credentials' => 'ecs']),
            new SesSdkTransport(
                $this->createCredentialsResolver(self::ECS_USER, self::ECS_PASSWORD),
                'eu-west-1',
                $dispatcher,
                $logger
            ),
        ];

        yield [
            new Dsn('ses+sdk', 'default', null, null, null),
            new SesSdkTransport(
                $this->createCredentialsResolver(self::DEFAULT_USER, self::DEFAULT_PASSWORD),
                'eu-west-1',
                $dispatcher,
                $logger
            ),
        ];
    }

    public function unsupportedSchemeProvider(): iterable
    {
        yield [
            new Dsn('foobar', 'default', self::USER, self::PASSWORD),
            'The "foobar" scheme is not supported.',
        ];
    }

    private function createCredentialsResolver($key, $secret)
    {
        return function () use ($key, $secret) {
            $promise = new Promise();
            $promise->resolve(new Credentials($key, $secret));
            return $promise;
        };
    }

    protected function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher ?? $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    protected function getClient(): HttpClientInterface
    {
        return $this->client ?? $this->client = $this->createMock(HttpClientInterface::class);
    }

    protected function getLogger(): LoggerInterface
    {
        return $this->logger ?? $this->logger = $this->createMock(LoggerInterface::class);
    }
}