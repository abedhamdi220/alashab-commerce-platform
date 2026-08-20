<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * يعرض فقط ملفات معرض المنتجات من القرص المسجل لها.
     *
     * استخدام Storage::response() يجعل الوصول مستقلاً عن public/storage،
     * ويحافظ على نوع المحتوى الصحيح (image/jpeg, image/png, ...).
     */
    public function show(Media $media): StreamedResponse
    {
        abort_unless(
            $media->model_type === Product::class
            && $media->collection_name === 'product_gallery',
            404
        );

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $media->file_name, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
