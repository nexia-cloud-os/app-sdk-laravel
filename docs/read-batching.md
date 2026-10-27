# Optional read batching

Standard Resource list/read routes are discovered automatically. For a custom finite JSON GET handler, explicitly declare that it is read-only:

```php
use Nexia\AppRuntime\BatchableRead;

#[BatchableRead]
public function index(Request $request): JsonResponse
{
    // Keep the existing permission and organization checks here.
}
```

The runtime catalog exporter discovers actual registered routes for that method. No route strings or new client API calls are required. Both canonical and legal-entity routes can participate. Downloads, streams, mutations and undeclared GET handlers must remain individual; do not mark them with this attribute. GET alone is not a read-only declaration.

The existing SDK HTTP API uses the host transport. The host groups at most eight compatible reads and splits larger groups automatically. Different organization contexts stay separate. Each member retains its HTTP middleware, authorization, response/error and cancellation semantics. Supported hosts deliver completed members incrementally, so a slow sibling does not delay later dependent reads. Older hosts may return the complete JSON envelope. A safe preflight rejection falls back to ordinary individual requests before any App read executes.

Development-only `[NEXIA_READ_BATCH_INFO]` messages appear in the browser console and the connected `nexia dev` terminal. They are informational, bounded and deduplicated; no request query values are logged. `nexia check` explains that source checks cannot determine runtime route registration. These hints do not fail builds, submission or deployment; real authorization and catalog/security errors still apply.

Compatibility: available in PHP SDK 0.10.14 with Core 0.8.161 and Manager 0.1.0-alpha.85. Deploy the Core reader before the Manager exporter and publish/adopt the PHP SDK before declaring new handlers in Apps. Refresh the App catalog through the usual build/deployment or development-source update. Existing Apps without declarations remain usable and keep individual custom reads. Local source validation does not prove a published SDK or immutable App image has adopted the change.

[Host SDK guide](https://developers.nexia.to/docs/app-sdk-sdk-contracts) · [Attribute source](https://github.com/nexia-cloud-os/app-sdk-laravel/blob/v0.10.14/src/AppRuntime/BatchableRead.php)
