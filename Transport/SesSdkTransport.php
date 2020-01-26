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

use Aws\Ses\Exception\SesException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Aws\SesV2\SesV2Client;

/**
 * @author Byron Adams
 */
class SesSdkTransport extends AbstractTransport
{
    private $client;

    public function __toString(): string
    {
        return sprintf('ses+sdk://...@%s', $this->client->getCredentials(), $this->getEndpoint());
    }

    public function __construct(
        callable $credentials,
        string $region = null,
        EventDispatcherInterface $dispatcher = null,
        LoggerInterface $logger = null
    ) {
        $this->client = new SesV2Client([
            'version' => 'latest',
            'region' => $region ?: 'eu-west-1',
            'credentials' => $credentials,
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
            throw new TransportException(sprintf(
                'Unable to send an email: %s (code %s).',
                $exception->getAwsErrorMessage() ?: $exception->getMessage(),
                $exception->getStatusCode() ?: $exception->getCode()
            ));
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
                'ToAddresses' => $this->stringifyAddresses($envelope->getRecipients()),
                'CcAddresses' => $this->stringifyAddresses($email->getCc()),
                'BccAddresses' => $this->stringifyAddresses($email->getBcc()),
            ],
            'Content' => [
                'Raw' => ['Data' => $email->toString()]
            ]
        ];
    }
}