<?php

/*
 * This file is part of the badams/symfony-amazon-sdk-mailer package.
 *
 * (c) Byron Adams <byron.adams54@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Badams\AmazonMailerSdk\SesSdkTransport;
use GuzzleHttp\Promise\Promise;
use Aws\Credentials\Credentials;
use Symfony\Component\Mime\Email;
use Aws\MockHandler;
use Aws\CommandInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Mime\Address;
use Badams\AmazonMailerSdk\SesSdkTransportConfig;


class SesSdkTransportTest extends \PHPUnit\Framework\TestCase
{
    public function testSend()
    {
        $email = (new Email())
            ->from('test@ses-sdk.com')
            ->addTo('to@test.com')
            ->addCc('cc@test.com')
            ->addCc('cc1@test.com')
            ->addBcc('bcc@test.com')
            ->text('This is a test email');

        $mockHandler = function (CommandInterface $cmd) use ($email) {
            $data = $cmd->toArray();
            $this->assertEquals($email->getFrom(), [new Address($data['FromEmailAddress'])]);
            $this->assertArrayHasKey('Destination', $data);
            $this->assertEquals($email->getTo(), [new Address($data['Destination']['ToAddresses'][0])]);
            $this->assertEquals($email->getCc(), [
                new Address($data['Destination']['CcAddresses'][0]),
                new Address($data['Destination']['CcAddresses'][1])
            ]);
            $this->assertEquals($email->getBcc(), [new Address($data['Destination']['BccAddresses'][0])]);

            $this->assertArrayHasKey('Content', $data);
            $this->assertStringContainsString($email->getTextBody(), $data['Content']['Raw']['Data']);

            return new \Aws\Result(['MessageId' => 'MESSAGE_ID_123']);
        };

        $transport = new SesSdkTransport(
            $this->createConfig('ACCESS_KEY', 'SECRET_KEY', 'eu-west-1'),
            null,
            null,
            $mockHandler);

        $sentMessage = $transport->send($email);
        $this->assertEquals('MESSAGE_ID_123', $sentMessage->getMessageId());
    }

    public function testSesError()
    {
        $email = (new Email())
            ->from('test@ses-sdk.com')
            ->addTo('to@test.com')
            ->text('Test');

        $mockHandler = function (CommandInterface $cmd) {
            throw new \Aws\Ses\Exception\SesException('ERRR', $cmd);
        };

        $transport = new SesSdkTransport(
            $this->createConfig('ACCESS_KEY', 'SECRET_KEY', 'eu-west-1'),
            null,
            null,
            $mockHandler);

        $this->expectException(\Symfony\Component\Mailer\Exception\TransportException::class);
        $this->expectExceptionMessage('Unable to send an email: ERRR (code 0).');
        $transport->send($email);
    }

    /**
     * @dataProvider toStringProvider
     *
     * @param SesSdkTransport $transport
     * @param string $expected
     */
    public function testToString(SesSdkTransport $transport, string $expected)
    {
        $this->assertSame($expected, (string)$transport);
    }


    public function toStringProvider(): iterable
    {
        yield [
            new SesSdkTransport($this->createConfig('ACCESS_KEY', 'SECRET_KEY', 'eu-east-1')),
            'ses+sdk://ACCESS_KEY:SECRET_KEY@eu-east-1'
        ];

        yield [
            new SesSdkTransport($this->createConfig('ACCESS_KEY', 'SECRET_KEY', 'us-east-1')),
            'ses+sdk://ACCESS_KEY:SECRET_KEY@us-east-1'
        ];

        yield [
            new SesSdkTransport(new SesSdkTransportConfig(function () {
                return new \GuzzleHttp\Promise\RejectedPromise('bad things happened');
            }, 'eu-west-1')),
            'ses+sdk://:@eu-west-1'
        ];
    }

    private function createConfig($key, $secret, $region, $options = [])
    {
        return new SesSdkTransportConfig(
            function () use ($key, $secret) {
                $promise = new Promise();
                $promise->resolve(new Credentials($key, $secret));
                return $promise;
            },
            $region,
            $options
        );
    }
}