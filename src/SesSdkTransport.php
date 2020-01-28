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

use Aws\Credentials\Credentials;
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

class SesSdkTransport extends AbstractTransport
{
    private $client;

    private $credentials;

    private $config;

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
        SesSdkTransportConfig $config,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null,
        $handler = null
    ) {
        $this->config = $config;

        $this->client = new SesV2Client([
            'version' => 'latest',
            'region' => $this->config->getRegion(),
            'credentials' => $this->config->getCredentials(),
            'handler' => $handler,
        ]);

        parent::__construct($dispatcher, $logger);
    }

    protected function doSend(SentMessage $message): void
    {
        try {
            $email = MessageConverter::toEmail($message->getOriginalMessage());
            $response = $this->doSendSdk($email, $message->getEnvelope());
            $message->setMessageId((string) $response->get('MessageId'));
        } catch (SesException $exception) {
            $message = $exception->getAwsErrorMessage() ?: $exception->getMessage();
            $code = $exception->getStatusCode() ?: $exception->getCode();
            throw new TransportException(sprintf('Unable to send an email: %s (code %s).', $message, $code));
        }
    }

    protected function doSendSdk(Email $email, Envelope $envelope): \Aws\Result
    {
        return $this->client->sendEmail($this->getPayload($email, $envelope));
    }

    protected function getPayload(Email $email, Envelope $envelope)
    {
        return array_merge($this->config->getOptions(), [
            'FromEmailAddress' => $envelope->getSender()->toString(),
            'Destination' => [
                'ToAddresses' => $this->stringifyAddresses($email->getTo()),
                'CcAddresses' => $this->stringifyAddresses($email->getCc()),
                'BccAddresses' => $this->stringifyAddresses($email->getBcc()),
            ],
            'Content' => [
                'Raw' => ['Data' => $email->toString()],
            ],
        ]);
    }

    /**
     * @return Credentials
     */
    protected function getCredentials()
    {
        if (null === $this->credentials) {
            $this->credentials = $this->client->getCredentials()->wait();
        }

        return $this->credentials;
    }
}
