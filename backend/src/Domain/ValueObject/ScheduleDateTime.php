<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use App\Domain\Exception\InvalidScheduleException;

class ScheduleDateTime
{
    private DateTimeImmutable $value;

    public function __construct(string $dateTimeStr)
    {
        $timezoneName = getenv('APP_TIMEZONE') ?: 'UTC';
        $timezone = new DateTimeZone($timezoneName);

        try {
            $dt = new DateTimeImmutable($dateTimeStr, $timezone);
        } catch (\Exception $e) {
            throw new InvalidScheduleException('Data e hora no formato inválido.');
        }

        $now = new DateTimeImmutable('now', $timezone);
        if ($dt <= $now) {
            throw new InvalidScheduleException('O agendamento deve ser para uma data e hora futura e em um fuso horário válido.');
        }

        $this->value = $dt;
    }

    public function getValue(): DateTimeImmutable
    {
        return $this->value;
    }

    public function format(string $format): string
    {
        return $this->value->format($format);
    }
}
