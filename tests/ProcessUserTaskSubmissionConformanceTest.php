<?php

declare(strict_types=1);

use Nexia\AppDescriptors\ProcessUserTaskFormDescriptor;
use Nexia\Process\Contracts\ProcessUserTaskSubmissionHandler;
use Nexia\Process\Contracts\ProcessUserTaskSubmissionRegistrar;
use Nexia\Testing\ProcessUserTaskSubmissionConformance;

require dirname(__DIR__).'/vendor/autoload.php';

$descriptor = new ProcessUserTaskFormDescriptor(
    key: 'sample.review_widget',
    appKey: 'sample',
    labelKey: 'sample.review_widget.label',
    rendering: [
        'mode' => 'slot_widget',
        'slot' => ProcessUserTaskFormDescriptor::FORM_SLOT,
        'component' => 'sample.review_widget',
        'slot_api_version' => 1,
    ],
    submissionActionKey: 'review.submit',
);

$registrar = new class implements ProcessUserTaskSubmissionRegistrar
{
    /** @var array<string, bool> */
    private array $registered = [];

    public function register(string $appKey, string $actionKey, callable $factory): void
    {
        $this->registered[$appKey.'::'.$actionKey] = true;
    }

    public function registered(string $appKey, string $actionKey): bool
    {
        return $this->registered[$appKey.'::'.$actionKey] ?? false;
    }
};

$missing = ProcessUserTaskSubmissionConformance::violations('sample', [$descriptor], $registrar);
if (count($missing) !== 1 || ! str_contains($missing[0], 'no handler is registered')) {
    throw new RuntimeException('Missing UserTask submission handler was not detected.');
}

$registrar->register(
    'sample',
    'review.submit',
    static fn (): ProcessUserTaskSubmissionHandler => throw new LogicException('factory is not invoked by conformance'),
);

ProcessUserTaskSubmissionConformance::assert('sample', [$descriptor], $registrar);

fwrite(STDOUT, "Process UserTask submission conformance is valid.\n");
