<?php

declare(strict_types=1);

namespace Nexia\AppRuntime;

use Composer\Semver\Semver;
use InvalidArgumentException;

/** App dependencies may select installed platform packages, never extend the operator lock. */
final class RuntimeDependencyPolicy
{
    public static function validate(array $app, array $lock, bool $tests = false): void
    {
        $production = array_column($lock['packages'] ?? [], null, 'name');
        $development = array_column($lock['packages-dev'] ?? [], null, 'name');
        $sdkNames = ['nexia-cloud-os/sdk-laravel', 'nexia/sdk-laravel', 'amuzcorp/nexia-app-sdk-laravel'];
        foreach (['require', 'require-dev'] as $field) {
            if ($field === 'require-dev' && ! $tests) {
                continue;
            }
            $requirements = $app[$field] ?? [];
            if (! is_array($requirements)) {
                throw new InvalidArgumentException('Invalid dependency requirements.');
            }
            foreach ($requirements as $name => $constraint) {
                if (! is_string($name) || ! is_string($constraint) || $constraint === '' || strlen($constraint) > 512) {
                    throw new InvalidArgumentException('Invalid dependency constraint.');
                }
                $version = null;
                if ($name === 'php') {
                    $version = PHP_VERSION;
                } elseif (str_starts_with($name, 'ext-')) {
                    $extension = substr($name, 4);
                    $version = extension_loaded($extension) ? (phpversion($extension) ?: '0') : null;
                } else {
                    $package = $production[$name] ?? ($field === 'require-dev' ? ($development[$name] ?? null) : null);
                    // Only the canonical SDK may satisfy its previous names, at its own version.
                    if ($package === null && in_array($name, array_slice($sdkNames, 1), true)) {
                        $sdk = $production[$sdkNames[0]] ?? null;
                        if (($sdk['replace'][$name] ?? null) === 'self.version') {
                            $package = $sdk;
                        }
                    }
                    $version = $package['version'] ?? null;
                    if ($package === null || (str_starts_with($name, 'amuzcorp/nexia-app-') && ! in_array($name, $sdkNames, true))) {
                        throw new InvalidArgumentException('Dependency is not in the approved '.($field === 'require' ? 'production Runtime' : 'test toolchain').'.');
                    }
                    foreach (array_keys($package['autoload']['psr-4'] ?? []) as $namespace) {
                        if (str_starts_with($namespace, 'Nexia\\Apps\\')) {
                            throw new InvalidArgumentException('App-to-App PHP dependencies are forbidden.');
                        }
                    }
                }
                try {
                    $compatible = $version !== null && Semver::satisfies($version, $constraint);
                } catch (\Throwable) {
                    throw new InvalidArgumentException('Invalid dependency version constraint.');
                }
                if (! $compatible) {
                    throw new InvalidArgumentException('Dependency constraint does not match the installed Runtime.');
                }
            }
        }
        if (array_intersect($sdkNames, array_keys($app['require'] ?? [])) === []) {
            throw new InvalidArgumentException('Apps must require the public nexia-cloud-os/sdk-laravel package.');
        }
        foreach (['replace', 'provide', 'repositories'] as $field) {
            if (! empty($app[$field])) {
                throw new InvalidArgumentException('Apps cannot replace, provide or resolve platform dependencies.');
            }
        }
        $autoload = $app['autoload'] ?? [];
        if (! is_array($autoload) || array_diff(array_keys($autoload), ['psr-4']) !== []
            || ! is_array($autoload['psr-4'] ?? null) || $autoload['psr-4'] === []) {
            throw new InvalidArgumentException('Apps must use their own PSR-4 source; executable autoload hooks are forbidden.');
        }
        $family = null;
        foreach (array_keys($autoload['psr-4']) as $namespace) {
            if (! is_string($namespace) || ! preg_match('/\ANexia\\\\Apps\\\\([A-Z][A-Za-z0-9]*)\\\\([A-Z][A-Za-z0-9]*)\\\\(?:[A-Z][A-Za-z0-9_]*\\\\)*\z/D', $namespace, $matches)) {
                throw new InvalidArgumentException('App source must use one registered App namespace family.');
            }
            $current = $matches[1].'\\'.$matches[2];
            if ($family !== null && $current !== $family) {
                throw new InvalidArgumentException('App source cannot claim another App namespace.');
            }
            $family = $current;
        }
    }
}
