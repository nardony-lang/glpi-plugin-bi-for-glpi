<?php

namespace GlpiPlugin\Biforglpi;

use InvalidArgumentException;

final class SqlReadOnlyGuard
{
    /** @var list<string> */
    private const FORBIDDEN_KEYWORDS = [
        'ALTER', 'ANALYZE', 'CALL', 'CHECK', 'CHECKSUM', 'COMMIT', 'CREATE',
        'DEALLOCATE', 'DELETE', 'DO', 'DROP', 'EXECUTE', 'FLUSH', 'GRANT',
        'HANDLER', 'INSERT', 'INSTALL', 'INTO', 'KILL', 'LOAD', 'LOCK',
        'OPTIMIZE', 'PREPARE', 'PROCEDURE', 'RELEASE', 'RENAME', 'REPAIR', 'REPLACE',
        'REVOKE', 'ROLLBACK', 'SAVEPOINT', 'SET', 'SHUTDOWN', 'START',
        'TRUNCATE', 'UNINSTALL', 'UNLOCK', 'UPDATE', 'USE',
    ];

    /** @var list<string> */
    private const FORBIDDEN_FUNCTIONS = [
        'BENCHMARK', 'GET_LOCK', 'IS_FREE_LOCK', 'IS_USED_LOCK', 'LOAD_FILE',
        'MASTER_POS_WAIT', 'RELEASE_ALL_LOCKS', 'RELEASE_LOCK', 'SLEEP',
        'SYS_EXEC', 'SYS_EVAL',
    ];

    public function validate(string $sql): string
    {
        $sql = trim($sql);
        if ($sql === '') {
            throw new InvalidArgumentException('Informe uma consulta SQL.');
        }

        if (strlen($sql) > 100_000) {
            throw new InvalidArgumentException('A consulta excede o limite de 100 KB.');
        }

        if (str_contains($sql, "\0")) {
            throw new InvalidArgumentException('A consulta contém caracteres inválidos.');
        }

        $lexicalSql = $this->removeQuotedValues($sql);
        if (preg_match('/(?:--[\t ]|#|\/\*)/', $lexicalSql) === 1) {
            throw new InvalidArgumentException('Comentários SQL não são permitidos.');
        }

        if (str_contains($lexicalSql, ';')) {
            throw new InvalidArgumentException('Envie apenas uma instrução, sem ponto e vírgula.');
        }

        if (str_contains($lexicalSql, ':=') || preg_match('/(^|[^A-Z0-9_$])@[A-Z0-9_$]+/i', $lexicalSql) === 1) {
            throw new InvalidArgumentException('Variáveis e atribuições de sessão não são permitidas.');
        }

        if (preg_match('/\bNEXT\s+VALUE\s+FOR\b/i', $lexicalSql) === 1) {
            throw new InvalidArgumentException('Avanço de sequências não é permitido.');
        }

        preg_match_all('/[A-Z_][A-Z0-9_$]*/i', $lexicalSql, $matches);
        $tokens = array_map('strtoupper', $matches[0]);
        if ($tokens === []) {
            throw new InvalidArgumentException('Não foi possível identificar a instrução SQL.');
        }

        $first = $tokens[0];
        if (!in_array($first, ['SELECT', 'WITH', 'EXPLAIN'], true)) {
            throw new InvalidArgumentException('Somente SELECT, WITH e EXPLAIN são permitidos.');
        }

        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (in_array($keyword, $tokens, true)) {
                throw new InvalidArgumentException(sprintf('A palavra-chave %s não é permitida.', $keyword));
            }
        }

        foreach (self::FORBIDDEN_FUNCTIONS as $function) {
            if (preg_match('/\b' . preg_quote($function, '/') . '\s*\(/i', $lexicalSql) === 1) {
                throw new InvalidArgumentException(sprintf('A função %s não é permitida.', $function));
            }
        }

        if ($first === 'WITH' && !in_array('SELECT', $tokens, true)) {
            throw new InvalidArgumentException('A CTE deve produzir um resultado SELECT.');
        }

        if ($first === 'EXPLAIN') {
            $explainedStatement = $this->findExplainedStatement($tokens);
            if (!in_array($explainedStatement, ['SELECT', 'WITH'], true)) {
                throw new InvalidArgumentException('EXPLAIN só pode analisar SELECT ou WITH.');
            }
        }

        return $sql;
    }

    private function removeQuotedValues(string $sql): string
    {
        $output = '';
        $quote = null;
        $length = strlen($sql);

        for ($index = 0; $index < $length; $index++) {
            $character = $sql[$index];
            if ($quote === null) {
                if ($character === "'" || $character === '"' || $character === '`') {
                    $quote = $character;
                    $output .= ' ';
                    continue;
                }
                $output .= $character;
                continue;
            }

            if ($character === '\\') {
                $output .= ' ';
                if ($index + 1 < $length) {
                    $output .= ' ';
                    $index++;
                }
                continue;
            }

            if ($character === $quote) {
                if ($index + 1 < $length && $sql[$index + 1] === $quote) {
                    $output .= '  ';
                    $index++;
                    continue;
                }
                $quote = null;
            }

            $output .= ' ';
        }

        if ($quote !== null) {
            throw new InvalidArgumentException('Há uma string ou identificador sem fechamento.');
        }

        return $output;
    }

    /** @param list<string> $tokens */
    private function findExplainedStatement(array $tokens): ?string
    {
        $index = 1;
        if (($tokens[$index] ?? null) === 'FORMAT') {
            $index += 2;
        }

        return $tokens[$index] ?? null;
    }
}
