<?php

namespace GlpiPlugin\Biforglpi;

use DateTimeImmutable;
use InvalidArgumentException;

final class SqlTemplate
{
    public const TOKENS = ['entity_id', 'date_start', 'date_end', 'date_end_exclusive'];

    public function validate(string $sql): string
    {
        $sql = trim($sql);
        preg_match_all('/\{\{([a-z_]+)\}\}/', $sql, $matches);
        foreach ($matches[1] as $token) {
            if (!in_array($token, self::TOKENS, true)) {
                throw new InvalidArgumentException('Variável de consulta desconhecida: {{' . $token . '}}.');
            }
        }
        $withoutKnownTokens = preg_replace('/\{\{(?:' . implode('|', self::TOKENS) . ')\}\}/', '', $sql);
        if (is_string($withoutKnownTokens) && (str_contains($withoutKnownTokens, '{{') || str_contains($withoutKnownTokens, '}}'))) {
            throw new InvalidArgumentException('Há uma variável de consulta inválida ou incompleta.');
        }
        (new SqlReadOnlyGuard())->validate($this->compile($sql, [
            'entity_id' => 1,
            'date_start' => '2026-01-01',
            'date_end' => '2026-01-31',
        ], false));
        return $sql;
    }

    /** @param array{entity_id:int,date_start:string,date_end:string} $context */
    public function compile(string $sql, array $context, bool $validate = true): string
    {
        if ($validate) {
            $this->validate($sql);
        }
        $start = $this->date($context['date_start']);
        $end = $this->date($context['date_end']);
        if ($start > $end) {
            throw new InvalidArgumentException('A data inicial deve ser anterior à data final.');
        }
        return strtr($sql, [
            '{{entity_id}}' => (string) max(0, (int) $context['entity_id']),
            '{{date_start}}' => "'" . $start->format('Y-m-d') . " 00:00:00'",
            '{{date_end}}' => "'" . $end->format('Y-m-d') . " 23:59:59'",
            '{{date_end_exclusive}}' => "'" . $end->modify('+1 day')->format('Y-m-d') . " 00:00:00'",
        ]);
    }

    private function date(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Informe datas válidas no formato AAAA-MM-DD.');
        }
        return $date;
    }
}
