<?php

namespace Botble\LarkWebhook\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class LarkBaseClient
{
    public function __construct(
        protected ?string $appId = null,
        protected ?string $appSecret = null,
        protected ?string $appToken = null,
        protected ?string $tableId = null,
        protected ?string $domain = null,
    ) {
        $this->appId ??= setting('lark_webhook_app_id');
        $this->appSecret ??= setting('lark_webhook_app_secret');
        $this->appToken ??= setting('lark_webhook_base_app_token');
        $this->tableId ??= setting('lark_webhook_base_table_id');
        $this->domain ??= setting('lark_webhook_base_domain', 'https://open.larksuite.com');
    }

    public function isConfigured(): bool
    {
        return $this->appId && $this->appSecret && $this->appToken && $this->tableId;
    }

    /**
     * Exchange App ID + App Secret for a tenant_access_token (cached until shortly before it expires).
     *
     * @throws \RuntimeException|ConnectionException
     */
    public function tenantAccessToken(): string
    {
        $cacheKey = 'lark_webhook_tenant_token_' . md5($this->domain . $this->appId);

        return Cache::remember($cacheKey, now()->addMinutes(90), function (): string {
            $response = Http::asJson()
                ->timeout(15)
                ->post($this->url('/open-apis/auth/v3/tenant_access_token/internal'), [
                    'app_id' => $this->appId,
                    'app_secret' => $this->appSecret,
                ]);

            $data = $response->json();

            if (($data['code'] ?? -1) !== 0) {
                throw new \RuntimeException(
                    'Lark auth failed: ' . ($data['msg'] ?? $response->body())
                );
            }

            return $data['tenant_access_token'];
        });
    }

    /**
     * Create one record in the configured Lark Base table.
     *
     * @param  array<string, mixed>  $fields  Map of Base field name => value.
     * @return array The `record` object returned by Lark.
     *
     * @throws \RuntimeException|ConnectionException
     */
    public function createRecord(array $fields): array
    {
        throw_unless($this->isConfigured(), new \RuntimeException('Lark Base push is not fully configured.'));

        $response = Http::withToken($this->tenantAccessToken())
            ->asJson()
            ->timeout(15)
            ->post(
                $this->url("/open-apis/bitable/v1/apps/{$this->appToken}/tables/{$this->tableId}/records"),
                ['fields' => $fields]
            );

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            // A stale cached token is the most common failure — drop it so the next call re-auths.
            if (in_array($data['code'] ?? null, [99991663, 99991664, 99991661], true)) {
                Cache::forget('lark_webhook_tenant_token_' . md5($this->domain . $this->appId));
            }

            throw new \RuntimeException('Lark create record failed: ' . ($data['msg'] ?? $response->body()));
        }

        return $data['data']['record'] ?? [];
    }

    /**
     * List the field definitions of the target table. Useful to discover which field
     * is an attachment (type 17) before pushing images.
     *
     * @return array<int, array{field_name: string, type: int, ...}>
     *
     * @throws \RuntimeException|ConnectionException
     */
    public function listFields(): array
    {
        throw_unless($this->isConfigured(), new \RuntimeException('Lark Base push is not fully configured.'));

        $response = Http::withToken($this->tenantAccessToken())
            ->timeout(15)
            ->get($this->url("/open-apis/bitable/v1/apps/{$this->appToken}/tables/{$this->tableId}/fields"), [
                'page_size' => 100,
            ]);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            throw new \RuntimeException('Lark list fields failed: ' . ($data['msg'] ?? $response->body()));
        }

        return $data['data']['items'] ?? [];
    }

    /**
     * Upload a binary file (e.g. an image) into the Base so it can be attached to a record.
     * Returns the file_token to put into an attachment field.
     *
     * @param  string  $contents  Raw file bytes.
     * @param  string  $fileName  Name shown in Lark (keep the correct extension).
     *
     * @throws \RuntimeException|ConnectionException
     */
    public function uploadMedia(string $contents, string $fileName, string $parentType = 'bitable_image'): string
    {
        throw_unless($this->appToken, new \RuntimeException('Base App Token is required to upload media.'));

        $response = Http::withToken($this->tenantAccessToken())
            ->timeout(60)
            ->attach('file', $contents, $fileName)
            ->post($this->url('/open-apis/drive/v1/medias/upload_all'), [
                'file_name' => $fileName,
                'parent_type' => $parentType,
                // Drive needs the real Base obj_token, which differs from a wiki-node token.
                'parent_node' => $this->realAppToken(),
                'size' => (string) strlen($contents),
            ]);

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            throw new \RuntimeException('Lark media upload failed: ' . ($data['msg'] ?? $response->body()));
        }

        return $data['data']['file_token'];
    }

    /**
     * Upload an image from a local path or a URL and return its file_token.
     *
     * @throws \RuntimeException|ConnectionException
     */
    public function uploadImageFrom(string $pathOrUrl, ?string $fileName = null): string
    {
        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            $contents = Http::timeout(60)->get($pathOrUrl)->throw()->body();
        } else {
            throw_unless(is_file($pathOrUrl), new \RuntimeException("File not found: {$pathOrUrl}"));
            $contents = file_get_contents($pathOrUrl);
        }

        return $this->uploadMedia($contents, $fileName ?: basename(parse_url($pathOrUrl, PHP_URL_PATH) ?: 'image.jpg'));
    }

    /**
     * Format one or more uploaded file_tokens for an attachment field value.
     *
     * @param  string|array<int, string>  $fileTokens
     * @return array<int, array{file_token: string}>
     */
    public static function attachment(string|array $fileTokens): array
    {
        return array_map(
            fn (string $token) => ['file_token' => $token],
            (array) $fileTokens
        );
    }

    /**
     * Delete a record by id.
     *
     * @throws \RuntimeException|ConnectionException
     */
    public function deleteRecord(string $recordId): void
    {
        $response = Http::withToken($this->tenantAccessToken())
            ->timeout(15)
            ->delete($this->url("/open-apis/bitable/v1/apps/{$this->appToken}/tables/{$this->tableId}/records/{$recordId}"));

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            throw new \RuntimeException('Lark delete record failed: ' . ($data['msg'] ?? $response->body()));
        }
    }

    /**
     * Validate the whole outbound config without needing to know the table's field names:
     * authenticate, then read one record from the target table.
     *
     * @throws \RuntimeException|ConnectionException
     */
    public function verifyConnection(): void
    {
        throw_unless($this->isConfigured(), new \RuntimeException('Lark Base push is not fully configured.'));

        $response = Http::withToken($this->tenantAccessToken())
            ->timeout(15)
            ->get(
                $this->url("/open-apis/bitable/v1/apps/{$this->appToken}/tables/{$this->tableId}/records"),
                ['page_size' => 1]
            );

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            throw new \RuntimeException('Lark Base check failed: ' . ($data['msg'] ?? $response->body()));
        }
    }

    /**
     * Run auth → read → write (create+delete) → image upload and report exactly which
     * capability is missing. Used by the "Test connection" button.
     *
     * @return array{ok: bool, checks: array<int, array{key: string, ok: bool, detail: string}>}
     */
    public function diagnose(): array
    {
        $checks = [];

        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'checks' => [[
                    'key' => 'config',
                    'ok' => false,
                    'detail' => 'Missing App ID, App Secret, Base App Token or Table ID.',
                ]],
            ];
        }

        // 1) Authenticate (App ID + App Secret).
        try {
            $this->tenantAccessToken();
            $checks[] = ['key' => 'auth', 'ok' => true, 'detail' => 'App ID / App Secret valid.'];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'checks' => [['key' => 'auth', 'ok' => false, 'detail' => $e->getMessage()]],
            ];
        }

        // 2) Read the table (read permission + collaborator access).
        $fields = [];

        try {
            $fields = $this->listFields();
            $checks[] = ['key' => 'read', 'ok' => true, 'detail' => 'Can read the table.'];
        } catch (\Throwable $e) {
            $checks[] = ['key' => 'read', 'ok' => false, 'detail' => $e->getMessage()];
        }

        // 3) Write: create a probe record (in a real text field) then delete it, so nothing is left behind.
        try {
            $record = $this->createRecord($this->probeFields($fields));
            $recordId = $record['record_id'] ?? null;

            if ($recordId) {
                try {
                    $this->deleteRecord($recordId);
                } catch (\Throwable) {
                    // Created but couldn't clean up — write still works.
                }
            }

            $checks[] = ['key' => 'write', 'ok' => true, 'detail' => 'Can create records.'];
        } catch (\Throwable $e) {
            $checks[] = ['key' => 'write', 'ok' => false, 'detail' => $e->getMessage()];
        }

        // 4) Upload a 1x1 image to Drive (drive scope + edit access on the Base).
        try {
            $this->uploadMedia($this->healthCheckPng(), 'lark-healthcheck.png');
            $checks[] = ['key' => 'upload', 'ok' => true, 'detail' => 'Can upload images.'];
        } catch (\Throwable $e) {
            $checks[] = ['key' => 'upload', 'ok' => false, 'detail' => $e->getMessage()];
        }

        return [
            'ok' => ! in_array(false, array_column($checks, 'ok'), true),
            'checks' => $checks,
        ];
    }

    /**
     * Build a minimal valid record body for the write probe: put a marker into the first
     * text field (type 1) so Lark accepts it. Falls back to the first field of any type.
     *
     * @param  array<int, array{field_name: string, type: int}>  $fields
     * @return array<string, mixed>
     */
    protected function probeFields(array $fields): array
    {
        $marker = '__healthcheck__';

        foreach ($fields as $field) {
            if (($field['type'] ?? null) === 1) {
                return [$field['field_name'] => $marker];
            }
        }

        if (! empty($fields[0]['field_name'])) {
            return [$fields[0]['field_name'] => $marker];
        }

        return ['__healthcheck__' => $marker];
    }

    protected function healthCheckPng(): string
    {
        // Smallest possible transparent 1x1 PNG.
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M8AAAMDAQC2r0z0AAAAAElFTkSuQmCC'
        );
    }

    /**
     * The configured Base App Token may be a wiki-node token (works for bitable read/write
     * because the API auto-resolves it, but NOT for Drive media upload). Resolve the real
     * obj_token via the Base metadata endpoint and cache it.
     *
     * @throws \RuntimeException|ConnectionException
     */
    public function realAppToken(): string
    {
        $cacheKey = 'lark_webhook_real_app_token_' . md5($this->domain . $this->appToken);

        return Cache::remember($cacheKey, now()->addHours(12), function (): string {
            $response = Http::withToken($this->tenantAccessToken())
                ->timeout(15)
                ->get($this->url("/open-apis/bitable/v1/apps/{$this->appToken}"));

            $data = $response->json();

            if (($data['code'] ?? -1) !== 0) {
                throw new \RuntimeException('Lark resolve app token failed: ' . ($data['msg'] ?? $response->body()));
            }

            return $data['data']['app']['app_token'] ?? $this->appToken;
        });
    }

    protected function url(string $path): string
    {
        return rtrim($this->domain, '/') . $path;
    }
}
