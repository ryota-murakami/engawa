<?php

namespace App\Jobs;

use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeleteMediaObject implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public string $disk, public string $path) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300, 900];
    }

    public function handle(): void
    {
        // 同じJobを再実行しても安全。参照が残っているobjectは削除しない。
        if (Media::query()->where('disk', $this->disk)->where('path', $this->path)->exists()) {
            return;
        }

        $disk = Storage::disk($this->disk);

        if ($disk->exists($this->path) && ! $disk->delete($this->path)) {
            throw new RuntimeException('画像objectを削除できませんでした。');
        }
    }
}
