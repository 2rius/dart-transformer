<?php

use M2rius\DartTransformer\DartTransformer;

it('discovers classes from auto_discover_types and applies type replacements', function () {
    // Create a temp app directory with a simple Data class using DateTime
    $tempDir = 'tests/tmp_app';
    @mkdir($tempDir, 0777, true);

    $php = <<<'PHP'
<?php
namespace TmpApp; 
use Spatie\LaravelData\Data; 
class TmpUser extends Data { public function __construct(public \DateTimeImmutable $registeredAt) {} }
PHP;

    file_put_contents($tempDir.'/TmpUser.php', $php);

    $config = [
        'auto_discover_types' => [$tempDir],
        'output_file' => 'tests/dart/discovered.dart',
        'default_type_replacements' => [
            \DateTimeImmutable::class => 'String',
        ],
    ];

    $transformer = new DartTransformer($config);
    $result = $transformer->generate();

    expect($result['path'])->toBe('tests/dart/discovered.dart');
    $content = file_get_contents($result['path']);
    expect($content)->toContain('class TmpUser');
    expect($content)->toContain('final String registeredAt;');

    // cleanup
    unlink($result['path']);
    @unlink($tempDir.'/TmpUser.php');
    @rmdir($tempDir);
    @rmdir('tests/dart');
});
