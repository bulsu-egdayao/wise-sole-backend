<?php

namespace App\Http\Controllers;

use App\Models\SiteImage;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class SiteImageController extends Controller
{
    private const ALLOWED_KEYS = ['hero_main', 'hero_side_1', 'hero_side_2', 'about_photo'];

    public function index()
    {
        $images = SiteImage::all()->pluck('image_path', 'key');
        return response()->json($images);
    }

    public function upload(Request $request, string $key)
    {
        if (!in_array($key, self::ALLOWED_KEYS)) {
            return response()->json(['message' => 'Invalid image key'], 422);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $existing = SiteImage::where('key', $key)->first();
        if ($existing) {
            CloudinaryService::deleteByUrl($existing->image_path);
        }

        $url = CloudinaryService::upload($request->file('image'), 'site');

        $siteImage = SiteImage::updateOrCreate(
            ['key' => $key],
            ['image_path' => $url]
        );

        return response()->json($siteImage, 201);
    }

    public function destroy(string $key)
    {
        $siteImage = SiteImage::where('key', $key)->first();

        if (!$siteImage) {
            return response()->json(['message' => 'No image set for this key'], 404);
        }

        CloudinaryService::deleteByUrl($siteImage->image_path);
        $siteImage->delete();

        return response()->json(['message' => 'Image removed']);
    }
}