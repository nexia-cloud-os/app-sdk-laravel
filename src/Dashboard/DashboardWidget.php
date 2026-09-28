<?php

declare(strict_types=1);

namespace Nexia\Dashboard;

use InvalidArgumentException;

final class DashboardWidget
{
    /**
     * `titleKey` is an exact locale-catalog key; code never carries translated
     * copy. The single `spec` is language-neutral: text roles inside it carry
     * catalog keys (`props.titleKey`), and the host materializes localized
     * text at the payload boundary. `description` is agent-facing technical
     * metadata (widget selection guidance), not user-visible copy, so it
     * stays a literal English string.
     *
     * @param  array<string, mixed>  $spec
     * @param  list<DashboardWidgetParameter>  $parameters
     * @param  list<string>  $requiredAnyPermission
     */
    public function __construct(
        public readonly string $key,
        public readonly string $titleKey,
        public readonly string $description,
        public readonly array $spec,
        public readonly DashboardWidgetKind $kind,
        public readonly array $parameters = [],
        public readonly ?string $reportingViewKey = null,
        public readonly ?string $familyKey = null,
        public readonly RendererCapability $capability = RendererCapability::HumanOnly,
        public readonly array $requiredAnyPermission = [],
        public readonly bool $discoverable = true,
    ) {
        if ($titleKey === '' || $titleKey !== trim($titleKey)) {
            throw new InvalidArgumentException("DashboardWidget {$key} titleKey must be a non-blank catalog key");
        }

        if ($spec === [] || ! isset($spec['component'])) {
            throw new InvalidArgumentException("DashboardWidget {$key} spec is not a ComponentSpec dict");
        }

        $seenParamNames = [];

        foreach ($parameters as $parameter) {
            if (! $parameter instanceof DashboardWidgetParameter) {
                throw new InvalidArgumentException(
                    "DashboardWidget {$key} has a non-DashboardWidgetParameter entry in parameters",
                );
            }

            if (isset($seenParamNames[$parameter->name])) {
                throw new InvalidArgumentException(
                    "DashboardWidget {$key} declares parameter '{$parameter->name}' twice",
                );
            }

            $seenParamNames[$parameter->name] = true;
        }

        if ($reportingViewKey !== null && trim($reportingViewKey) === '') {
            throw new InvalidArgumentException("DashboardWidget {$key} declares an empty reporting view key");
        }

        if ($familyKey !== null && trim($familyKey) === '') {
            throw new InvalidArgumentException("DashboardWidget {$key} declares an empty family key");
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $row = [
            'key' => $this->key,
            'title_key' => $this->titleKey,
            'description' => $this->description,
            'kind' => $this->kind->value,
            'spec' => $this->spec,
        ];

        if ($this->parameters !== []) {
            $row['parameters'] = array_map(
                static fn (DashboardWidgetParameter $parameter): array => $parameter->toArray(),
                $this->parameters,
            );
        }

        if ($this->reportingViewKey !== null) {
            $row['reporting_view_key'] = $this->reportingViewKey;
        }

        if ($this->familyKey !== null) {
            $row['family_key'] = $this->familyKey;
        }

        if ($this->requiredAnyPermission !== []) {
            $row['required_any_permission'] = array_values($this->requiredAnyPermission);
        }

        return $row;
    }
}
