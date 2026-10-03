<?php

declare(strict_types=1);

$manifest = json_decode((string) file_get_contents(__DIR__.'/../contract/retired-symbols.json'), true, flags: JSON_THROW_ON_ERROR);
$symbol = current(array_filter($manifest['php'], fn ($name) => str_ends_with($name, '\\ResourceActionDescriptor')));
$temporary = tempnam(sys_get_temp_dir(), 'retired-symbols-');
$fixture = $temporary.'.php';
rename($temporary, $fixture);

try {
    foreach ([
        ["<?php assert(str_contains(\$typescript, 'ResourceActionDescriptor[]'));", 0],
        ["<?php // ResourceActionDescriptor is a frontend type\n", 0],
        ['<?php new ResourceActionDescriptor();', 1],
        ['<?php use '.$symbol.' as Legacy; new Legacy();', 1],
        ["<?php \$type = '".$symbol."';", 1],
    ] as [$source, $expected]) {
        file_put_contents($fixture, $source);
        $process = proc_open([PHP_BINARY, __DIR__.'/../bin/check-retired-symbols.php', $fixture], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        assert(is_resource($process));
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        assert(proc_close($process) === $expected, $output);
    }
} finally {
    unlink($fixture);
}

echo "Retired PHP code and frontend string distinction passed.\n";
