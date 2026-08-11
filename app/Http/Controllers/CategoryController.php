<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::withCount('products')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json($category, 201);
    }

    public function show(Category $category)
    {
        return $category->load('products');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json($category);
    }

  public function destroy(Category $category)
{
    if ($category->products()->count() > 0) {
        return response()->json([
            'message' => 'Cannot delete a category that still has products. Move or delete its products first.',
        ], 422);
    }

    $category->delete();
    return response()->json(['message' => 'Category deleted']);
}

public function uploadImage(Request $request, Category $category)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $path = $request->file('image')->store('categories', 'public');
        $category->update(['image_path' => $path]);

        return response()->json($category);
    }

    public function uploadHoverImage(Request $request, Category $category)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($category->hover_image_path) {
            Storage::disk('public')->delete($category->hover_image_path);
        }

        $path = $request->file('image')->store('categories', 'public');
        $category->update(['hover_image_path' => $path]);

        return response()->json($category);
    }

    public function destroyImage(Category $category)
    {
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
            $category->update(['image_path' => null]);
        }

        return response()->json($category);
    }

    public function destroyHoverImage(Category $category)
    {
        if ($category->hover_image_path) {
            Storage::disk('public')->delete($category->hover_image_path);
            $category->update(['hover_image_path' => null]);
        }

        return response()->json($category);
    }
}