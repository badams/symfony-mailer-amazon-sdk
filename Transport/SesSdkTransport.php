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
use Aws\Credentials\CredentialsInterface;
use Aws\Ses\Exception\SesException;
use Aws\SesV2\SesV2Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @author Byron Adams
 */
class SesSdkTransport extends AbstractTransport
{
    private $client;

    /**
     * @var CredentialsInterface
     */
    private $credentials;

    public function __toString(): string
    {
        try {
            $credentials = $this->getCredentials();
        } catch (\Exception $exception) {
            $credentials = new Credentials('', '');
        }

        return sprintf(
            'ses+sdk://%s:%s@%s',
            urlencode($credentials->getAccessKeyId()),
            urlencode($credentials->getSecretKey()),
            $this->client->getRegion()
        );
    }

    public function __construct(
        callable $credentials,
        string $region,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null,
        $handler = null
    ) {
        $this->client = new SesV2Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => $credentials,
            'handler' => $handler,
        ]);

        parent::__construct($dispatcher, $logger);
    }

    protected function doSend(SentMessage $message): void
    {
        try {
            $email = MessageConverter::toEmail($message->getOriginalMessage());
            $response = $this->doSendSdk($email, $message->getEnvelope());
            $message->setMessageId($response->get('MessageId'));
        } catch (SesException $exception) {
            throw new TransportException(sprintf('Unable to send an email: %s (code %s).', $exception->getAwsErrorMessage() ?: $exception->getMessage(), $exception->getStatusCode() ?: $exception->getCode()));
        }
    }

    protected function doSendSdk(Email $email, Envelope $envelope): \Aws\Result
    {
        return $this->client->sendEmail($this->getPayload($email, $envelope));
    }

    protected function getPayload(Email $email, Envelope $envelope)
    {
        return [
            'FromEmailAddress' => $envelope->getSender()->toString(),
            'Destination' => [
                'ToAddresses' => $this->stringifyAddresses($email->getTo()),
                'CcAddresses' => $this->stringifyAddresses($email->getCc()),
                'BccAddresses' => $this->stringifyAddresses($email->getBcc()),
            ],
            'Content' => [
                'Raw' => ['Data' => $email->toString()],
            ],
        ];
    }

    public function getCredentials()
    {
        if (null === $this->credentials) {
            $this->credentials = $this->client->getCredentials()->wait();
        }

        return $this->credentials;
    }
}
