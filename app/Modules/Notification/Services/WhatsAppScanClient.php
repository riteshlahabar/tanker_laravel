<?php

declare(strict_types=1);

namespace App\Modules\Notification\Services;

use App\Modules\Notification\Contracts\WhatsAppScanClientInterface;
use App\Modules\Notification\Support\GatewayResult;
use App\Modules\Notification\Support\PhoneNumber;
use App\Modules\Notification\Support\ScanQr;
use App\Modules\Notification\Support\ScanStatus;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * The one place the panel talks to the scan service.
 *
 * Every call but `/health` carries the key as an `x-api-key` header — not a
 * bearer token, which is what the older `NodeApiGateway` sends and why that
 * gateway cannot be pointed at this service.
 *
 * Nothing here throws: a service that is asleep, unreachable or misconfigured
 * comes back as an unreachable status or a failed result, because the scan
 * screen has to be able to say so rather than show a 500.
 */
final class WhatsAppScanClient implements WhatsAppScanClientInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly int $timeout = 60,
    ) {}

    public function configured(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '';
    }

    public function status(): ScanStatus
    {
        if (! $this->configured()) {
            return ScanStatus::unreachable(
                'WHATSAPP_SCAN_BASE_URL and WHATSAPP_SCAN_API_KEY are not set.',
            );
        }

        try {
            $response = $this->request()->get($this->url('/status'));
        } catch (Throwable $e) {
            return ScanStatus::unreachable($e->getMessage());
        }

        if (! $response->successful()) {
            return ScanStatus::unreachable($this->describe($response));
        }

        return ScanStatus::fromPayload($response->json() ?? []);
    }

    public function qr(): ScanQr
    {
        if (! $this->configured()) {
            return ScanQr::notReady('The scan service is not configured.');
        }

        try {
            $response = $this->request()->get($this->url('/qr'));
        } catch (Throwable $e) {
            return ScanQr::notReady($e->getMessage());
        }

        $body = $response->json() ?? [];

        // 404 is the service's ordinary "no code right now", not a failure.
        if ($response->status() === 404) {
            return ScanQr::notReady($this->message($body));
        }

        if (! $response->successful()) {
            return ScanQr::notReady($this->describe($response));
        }

        if ((bool) ($body['connected'] ?? false)) {
            return ScanQr::alreadyConnected();
        }

        $image = $body['qrImageDataUrl'] ?? null;

        return is_string($image) && $image !== ''
            ? ScanQr::image($image)
            : ScanQr::notReady($this->message($body));
    }

    public function send(string $phone, string $message): GatewayResult
    {
        if (! $this->configured()) {
            return GatewayResult::failed('The WhatsApp scan service is not configured.');
        }

        $to = PhoneNumber::qualify($phone);

        if ($to === '') {
            return GatewayResult::failed('No usable mobile number.');
        }

        return $this->post('/send', ['phone' => $to, 'message' => $message]);
    }

    public function logout(): GatewayResult
    {
        return $this->post('/logout');
    }

    public function restart(): GatewayResult
    {
        return $this->post('/restart');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $path, array $payload = []): GatewayResult
    {
        if (! $this->configured()) {
            return GatewayResult::failed('The WhatsApp scan service is not configured.');
        }

        try {
            $response = $this->request()->post($this->url($path), $payload);
        } catch (Throwable $e) {
            // Handed back rather than thrown: the queued job's retry and
            // backoff are what should decide whether to try again.
            return GatewayResult::failed($e->getMessage());
        }

        return $response->successful()
            ? GatewayResult::sent($this->message($response->json() ?? []) ?: 'ok')
            : GatewayResult::failed($this->describe($response));
    }

    private function request(): PendingRequest
    {
        return Http::timeout($this->timeout)
            ->withHeaders(['x-api-key' => $this->apiKey])
            ->acceptJson()
            ->asJson();
    }

    private function url(string $path): string
    {
        return $this->baseUrl.$path;
    }

    /**
     * The service puts the reason in `message` on every error shape it has,
     * so a refusal reads as a sentence in the log screen instead of a status
     * code — "WhatsApp is not connected. Scan QR first." being the one the
     * owner will actually meet.
     */
    private function describe(Response $response): string
    {
        $message = $this->message($response->json() ?? []);

        return $message !== null
            ? 'HTTP '.$response->status().': '.$message
            : 'HTTP '.$response->status().': '.$response->body();
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function message(array $body): ?string
    {
        $message = $body['message'] ?? null;

        return is_string($message) && $message !== '' ? $message : null;
    }
}
