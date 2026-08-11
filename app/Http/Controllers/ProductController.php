<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'sizes']);

        if ($request->filled('category')) {
            $query->whereHas('category', fn($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->filled('new')) {
            $query->where('is_new', true);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('size')) {
            $query->whereHas('sizes', function ($q) use ($request) {
                $q->where('size', $request->size)->where('stock', '>', 0);
            });
        }

        if ($request->boolean('on_sale')) {
            $query->whereNotNull('sale_price');
        }

        if ($request->boolean('in_stock')) {
            $query->where('is_available', true);
        }

        match ($request->get('sort')) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        return $query->paginate(12);
    }

    public function availableSizes(Request $request)
    {
        $query = ProductSize::query()->where('stock', '>', 0);

        if ($request->filled('category')) {
            $query->whereHas('product.category', fn($q) => $q->where('slug', $request->category));
        }

        $sizes = $query->distinct()->pluck('size')->values();

        return response()->json($sizes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer|min:0',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'sizes' => 'nullable|array',
            'sizes.*.size' => 'required_with:sizes|string|max:50',
            'sizes.*.stock' => 'required_with:sizes|integer|min:0',
        ]);

        if (!empty($validated['sale_price']) && $validated['sale_price'] >= $validated['price']) {
            return response()->json([
                'message' => 'Sale price must be lower than the regular price.',
                'errors' => ['sale_price' => ['Sale price must be lower than the regular price.']],
            ], 422);
        }

        $product = Product::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'sale_price' => $validated['sale_price'] ?? null,
            'category_id' => $validated['category_id'],
            'stock' => $validated['stock'],
            'is_available' => $validated['is_available'] ?? true,
            'is_featured' => $validated['is_featured'] ?? false,
            'is_new' => $validated['is_new'] ?? false,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'sort_order' => $index,
                ]);
            }
        }

        if (!empty($validated['sizes'])) {
            foreach ($validated['sizes'] as $sizeRow) {
                ProductSize::create([
                    'product_id' => $product->id,
                    'size' => $sizeRow['size'],
                    'stock' => $sizeRow['stock'],
                ]);
            }
        }

        return response()->json($product->load('images', 'category', 'sizes'), 201);
    }

    public function show(Product $product)
    {
        return $product->load(['category', 'images', 'sizes', 'inquiries' => fn($q) => $q->latest()]);
    }

    public function showBySlug(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->with(['category', 'images', 'sizes'])
            ->firstOrFail();

        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'stock' => 'sometimes|required|integer|min:0',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'sizes' => 'nullable|array',
            'sizes.*.size' => 'required_with:sizes|string|max:50',
            'sizes.*.stock' => 'required_with:sizes|integer|min:0',
        ]);

        $effectivePrice = $validated['price'] ?? $product->price;
        if (!empty($validated['sale_price']) && $validated['sale_price'] >= $effectivePrice) {
            return response()->json([
                'message' => 'Sale price must be lower than the regular price.',
                'errors' => ['sale_price' => ['Sale price must be lower than the regular price.']],
            ], 422);
        }

        $sizes = $validated['sizes'] ?? null;
        unset($validated['sizes']);

        // Explicitly allow clearing sale_price by sending an empty value
        if ($request->has('sale_price') && $request->input('sale_price') === '') {
            $validated['sale_price'] = null;
        }

        $product->update($validated);

        if ($sizes !== null) {
            $product->sizes()->delete();
            foreach ($sizes as $sizeRow) {
                ProductSize::create([
                    'product_id' => $product->id,
                    'size' => $sizeRow['size'],
                    'stock' => $sizeRow['stock'],
                ]);
            }
        }

        return response()->json($product->load('images', 'category', 'sizes'));
    }

    public function destroy(Product $product)
    {
        foreach ($product->images as $image) {
          Storage::disk('public')->delete($image->image_path);
        }
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    public function storeImages(Request $request, Product $product)
    {
        $request->validate([
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if (!$request->hasFile('images')) {
            return response()->json(['message' => 'No images provided'], 422);
        }

        $existingCount = $product->images()->count();

        foreach ($request->file('images') as $index => $file) {
            $path = $file->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $existingCount === 0 && $index === 0,
                'sort_order' => $existingCount + $index,
            ]);
        }

        return response()->json($product->fresh('images'), 201);
    }

    public function destroyImage(Product $product, ProductImage $image)
    {
        if ($image->product_id !== $product->id) {
            return response()->json(['message' => 'Image does not belong to this product'], 403);
        }

        $wasPrimary = $image->is_primary;

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        if ($wasPrimary) {
            $next = $product->images()->orderBy('sort_order')->first();
            if ($next) {
                $next->update(['is_primary' => true]);
            }
        }

        return response()->json($product->fresh('images'));
    }
}