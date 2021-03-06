<?php
declare(strict_types=1);

namespace adeynes\cucumber\mod;

abstract class IpPunishment extends SimplePunishment
{

    /** @var string */
    protected $ip;

    public function __construct(string $ip, string $reason, int $expiration, string $moderator)
    {
        $this->ip = $ip;
        parent::__construct($reason, $expiration, $moderator);
    }

    public static function from(array $row): self
    {
        return new static($row['ip'], $row['reason'], $row['expiration'], $row['moderator']);
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getData(): array
    {
        return parent::getData() + ['ip' => $this->getIp()];
    }

    public function getDataFormatted(): array
    {
        return parent::getDataFormatted() + ['ip' => $this->getIp()];
    }

}