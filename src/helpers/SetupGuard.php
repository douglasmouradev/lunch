<?php
declare(strict_types=1);

/** Impede execução de scripts setup via HTTP. */
function requireCli(): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Este script só pode ser executado pela linha de comando (CLI).' . PHP_EOL;
    exit(1);
}
