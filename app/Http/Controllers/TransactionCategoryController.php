<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransactionCategoryController extends Controller
{
    public function index()
    {
        return TransactionCategory::withCount('proofs')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $data['slug'] = Str::slug($data['name']);

        $category = TransactionCategory::create($data);

        return response()->json($category, 201);
    }

    public function update(Request $request, TransactionCategory $transactionCategory)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $data['slug'] = Str::slug($data['name']);

        $transactionCategory->update($data);

        return response()->json($transactionCategory);
    }

    public function destroy(TransactionCategory $transactionCategory)
    {
        if ($transactionCategory->proofs()->count() > 0) {
            return response()->json([
                'message' => 'This category still has proof photos attached. Delete those first.',
            ], 422);
        }

        $transactionCategory->delete();

        return response()->json(['message' => 'Deleted']);
    }
}