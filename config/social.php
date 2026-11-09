<?php

return [
    // Cloudで作成するprivate bucketのdisk名は s3 に統一する。
    'media_disk' => env('SOCIAL_MEDIA_DISK', 'local'),
    'image_max_kb' => 2048,
    'image_max_dimension' => 4096,
    'image_max_pixels' => 8_000_000,
    'image_output_dimension' => 1600,
    'diary_image_limit' => 3,
    // 通知一覧はこの範囲を再認可してからページ分割する。
    'notification_window' => 500,
];
