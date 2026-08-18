<?php

use GlpiPlugin\Biforglpi\SqlExecutor;
use GlpiPlugin\Biforglpi\SqlReadOnlyGuard;

include '../../../inc/includes.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['ok' => false, 'message' => 'Método não permitido.']);
    exit;
}

Session::checkRight('config', READ);

try {
    $sql = (new SqlReadOnlyGuard())->validate((string) ($_POST['sql'] ?? ''));
    $limit = filter_var(
        $_POST['limit'] ?? SqlExecutor::DEFAULT_LIMIT,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'default'   => SqlExecutor::DEFAULT_LIMIT,
                'min_range' => 1,
                'max_range' => SqlExecutor::MAX_LIMIT,
            ],
        ]
    );

    $result = (new SqlExecutor())->execute($sql, (int) $limit);
    echo json_encode(
        ['ok' => true] + $result,
        JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
}
