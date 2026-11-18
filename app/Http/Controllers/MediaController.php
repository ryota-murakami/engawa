<?php

namespace App\Http\Controllers;

use App\Jobs\DeleteMediaObject;
use App\Models\Diary;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaStorage;
use App\Services\SocialGraph;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class MediaController extends Controller
{
    /**
     * @throws Throwable
     */
    public function store(Request $request, Diary $diary, MediaStorage $storage): RedirectResponse
    {
        Gate::authorize('update', $diary);
        $file = $this->validatedImage($request, 'image');
        $created = null;

        try {
            DB::transaction(function () use ($request, $diary, $storage, $file, &$created): void {
                // コメント・退会と同じUser→Diaryの順でlockする。
                $owner = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $locked = Diary::query()->whereKey($diary->id)->lockForUpdate()->firstOrFail();
                Gate::authorize('update', $locked);
                $count = Media::query()->where('diary_id', $locked->id)->count();

                if ($count >= (int) config('social.diary_image_limit', 3)) {
                    throw ValidationException::withMessages(['image' => '画像は日記1件につき3枚までです。']);
                }

                $created = $storage->store($file, $owner, $locked);
            });
        } catch (Throwable $exception) {
            $this->cleanupRolledBackUpload($created);
            throw $exception;
        }

        return back();
    }

    public function show(Request $request, Media $media, SocialGraph $graph): StreamedResponse
    {
        if ($media->kind === 'diary') {
            $diary = $media->diary;
            abort_unless($diary !== null && Gate::allows('view', $diary), 404);
        } else {
            $owner = $media->user;
            abort_unless($media->kind === 'avatar' && $owner !== null, 404);
            abort_if($graph->blocked($request->user(), $owner), 404);
        }

        return $this->stream($media);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Request $request, Media $media, MediaStorage $storage): RedirectResponse
    {
        if ($media->kind === 'diary') {
            $diary = $media->diary;
            abort_unless($diary !== null, 404);
            Gate::authorize('update', $diary);
        } else {
            abort_unless((int) $media->user_id === (int) $request->user()->id, 404);
        }

        DB::transaction(function () use ($media, $storage): void {
            $locked = Media::query()->whereKey($media->id)->lockForUpdate()->first();
            if ($locked) {
                $storage->delete($locked);
            }
        });

        return back();
    }

    public function avatarStore(Request $request, MediaStorage $storage): RedirectResponse
    {
        $file = $this->validatedImage($request, 'avatar');
        $created = null;

        try {
            DB::transaction(function () use ($request, $storage, $file, &$created): void {
                $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                $previous = Media::query()->where('user_id', $user->id)->where('kind', 'avatar')->get();
                $created = $storage->store($file, $user);

                foreach ($previous as $media) {
                    $storage->delete($media);
                }
            });
        } catch (Throwable $exception) {
            $this->cleanupRolledBackUpload($created);
            throw $exception;
        }

        return back();
    }

    public function avatarShow(Request $request, User $user, SocialGraph $graph): StreamedResponse
    {
        abort_if($graph->blocked($request->user(), $user), 404);
        $avatar = Media::query()->where('user_id', $user->id)->where('kind', 'avatar')->latest('id')->firstOrFail();

        return $this->stream($avatar);
    }

    public function avatarDestroy(Request $request, MediaStorage $storage): RedirectResponse
    {
        DB::transaction(function () use ($request, $storage): void {
            $user = User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $avatars = Media::query()->where('user_id', $user->id)->where('kind', 'avatar')->get();
            foreach ($avatars as $avatar) {
                $storage->delete($avatar);
            }
        });

        return back();
    }

    private function validatedImage(Request $request, string $field): UploadedFile
    {
        $maxKb = (int) config('social.image_max_kb', 2048);
        $maxDimension = (int) config('social.image_max_dimension', 4096);
        $request->validate([
            $field => [
                'required', 'file', 'image',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:'.$maxKb,
                'dimensions:min_width=1,min_height=1,max_width='.$maxDimension.',max_height='.$maxDimension,
            ],
        ]);
        $file = $request->file($field);
        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([$field => '画像ファイルを1つ選んでください。']);
        }

        $realPath = $file->getRealPath();
        $size = $realPath === false ? false : @getimagesize($realPath);

        if ($size === false || (int) config('social.image_max_pixels', 8_000_000) < $size[0] * $size[1]) {
            throw ValidationException::withMessages([$field => '画像は800万画素以下に縮小してください。']);
        }

        $bytes = $file->get();
        if ($bytes === false) {
            throw ValidationException::withMessages([$field => '画像ファイルを読み込めませんでした。再度選択してください。']);
        }

        $pixels = @imagecreatefromstring($bytes);
        if ($pixels === false) {
            throw ValidationException::withMessages([$field => '画像を読み取れません。別のJPEG、PNG、WebPを選んでください。']);
        }
        unset($pixels);

        return $file;
    }

    private function stream(Media $media): StreamedResponse
    {
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);

        return $disk->response($media->path, null, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function cleanupRolledBackUpload(?Media $media): void
    {
        if ($media === null) {
            return;
        }

        try {
            // commit後のqueue障害でcatchへ入っても、参照行があれば削除しない。
            (new DeleteMediaObject($media->disk, $media->path))->handle();
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
