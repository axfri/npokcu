<?php

namespace App\Http\Controllers;

use App\Models\ProxyDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProxyDeliveryDownloadController extends Controller
{
    public function __invoke(Request $request, ProxyDelivery $proxyDelivery): StreamedResponse
    {
        Gate::authorize('download', $proxyDelivery);

        $disk = Storage::disk((string) config('deliveries.disk', 'private'));
        abort_unless($disk->exists($proxyDelivery->file_path), 404);

        $delivery = DB::transaction(function () use ($request, $proxyDelivery, $disk): ProxyDelivery {
            $lockedDelivery = ProxyDelivery::query()
                ->whereKey($proxyDelivery->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($request->user())->authorize('download', $lockedDelivery);
            abort_unless($disk->exists($lockedDelivery->file_path), 404);

            $downloadedAt = now();
            $lockedDelivery->forceFill([
                'download_count' => $lockedDelivery->download_count + 1,
                'last_downloaded_at' => $downloadedAt,
            ])->save();

            $lockedDelivery->downloadLogs()->create([
                'user_id' => $request->user()?->getKey(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'downloaded_at' => $downloadedAt,
            ]);

            return $lockedDelivery;
        }, 3);

        return $disk->download(
            $delivery->file_path,
            basename($delivery->original_filename),
            ['Content-Type' => 'text/plain; charset=UTF-8'],
        );
    }
}
