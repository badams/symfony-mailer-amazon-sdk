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

class SesSdkTransportConfig
{
    /**
     * @var callable
     */
    private $credentials;

    /**
     * @var string
     */
    private $region;

    /**
     * @var array
     */
    private $options = [];

    public function __construct(callable $credentials, string $region, array $options = [])
    {
        $this->credentials = $credentials;
        $this->region = $region;
        $this->options = array_filter($options);
    }

    public function getCredentials(): callable
    {
        return $this->credentials;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
