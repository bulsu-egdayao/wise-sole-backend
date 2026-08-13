<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductType::withCount('products');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        return $query->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
        ]);

        $data['slug'] = Str::slug($data['name']);

        $type = ProductType::create($data);

        return response()->json($type, 201);
    }

    public function update(Request $request, ProductType $productType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $data['slug'] = Str::slug($data['name']);

        $productType->update($data);

        return response()->json($productType);
    }

    public function destroy(ProductType $productType)
    {
        if ($productType->products()->count() > 0) {
            return response()->json([
                'message' => 'This type still has products tagged with it. Remove the tag from those products first.',
            ], 422);
        }

        $productType->delete();

        return response()->json(['message' => 'Deleted']);
    }
}