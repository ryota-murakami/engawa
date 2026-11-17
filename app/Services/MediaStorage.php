<?php

namespace App\Services;

use App\Jobs\DeleteMediaObject;
use App\Models\Diary;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MediaStorage
{
    /**
     * @throws Throwable
     */
    public function store(UploadedFile $file, User $user, ?Diary $diary = null): Media
    {
        $diskName = (string) config('social.media_disk', 'local');
        $kind = $diary === null ? 'avatar' : 'diary';
        $path = 'media/'.$user->id.'/'.$kind.'/'.Str::uuid().'.webp';
        $bytes = $this->normalizedBytes($file);
        $disk = Storage::disk($diskName);

        // R2のprivate bucketへper-object ACLを指定しない。
        if (! $disk->put($path, $bytes, ['ContentType' => 'image/webp'])) {
            throw new RuntimeException('画像を保存できませんでした。');
        }

        try {
            return Media::query()->create([
                'user_id' => $user->id,
                'diary_id' => $diary?->id,
                'kind' => $kind,
                'disk' => $diskName,
                'path' => $path,
                'mime' => 'image/webp',
                'size' => strlen($bytes),
            ]);
        } catch (Throwable $exception) {
            // DB保存前の失敗。DB transactionとobject storageは別資源。
            try {
                if (! $disk->delete($path)) {
                    report(new RuntimeException('DB保存失敗後の画像cleanupに失敗しました。'));
                }
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }
    }

    public function delete(Media $media): void
    {
        $disk = $media->disk;
        $path = $media->path;
        $media->delete();

        // 外側transactionがrollbackしたら物理画像を消さない。
        DeleteMediaObject::dispatch($disk, $path)->afterCommit();
    }

    private function normalizedBytes(UploadedFile $file): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('GDのWebP対応を有効にしてください。');
        }

        $max = config('social.image_output_dimension', 1600);
        if (! is_int($max) || $max < 1) {
            throw new RuntimeException('social.image_output_dimensionには正の整数を設定してください。');
        }

        // Laravel 13のImage APIで向きとサイズを補正。中間PNGは可逆形式。
        $png = Image::fromUpload($file)
            ->orient()
            ->scale($max, $max)
            ->toPng()
            ->toBytes();

        $source = @imagecreatefromstring($png);

        if ($source === false) {
            throw new RuntimeException('画像を展開できませんでした。');
        }

        // 新規canvasへ画素だけを転写し、EXIF/GPS/XMP等を引き継がない。
        $canvas = imagecreatetruecolor(imagesx($source), imagesy($source));

        if ($canvas === false) {
            throw new RuntimeException('画像canvasを作成できませんでした。');
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        if ($transparent === false) {
            throw new RuntimeException('画像canvasの透明色を作成できませんでした。');
        }
        imagefill($canvas, 0, 0, $transparent);
        imagecopy($canvas, $source, 0, 0, 0, 0, imagesx($source), imagesy($source));

        ob_start();
        try {
            $success = imagewebp($canvas, null, 80);
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        // imagewebpは内部失敗時にもtrueになり得るためbytesも検査する。
        if (! $success || ! is_string($bytes) || strlen($bytes) < 12 || substr($bytes, 8, 4) !== 'WEBP') {
            throw new RuntimeException('WebPへの変換に失敗しました。');
        }

        return $bytes;
    }
}
