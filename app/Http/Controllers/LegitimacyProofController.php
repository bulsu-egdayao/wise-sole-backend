<?php

namespace App\Http\Controllers;

use App\Models\LegitimacyProof;
use App\Models\TransactionCategory;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class LegitimacyProofController extends Controller
{
    public function index()
    {
        // Public endpoint: all categories with their proof photos, for the /legitimacy page
        return TransactionCategory::with('proofs')->get();
    }

    public function store(Request $request, CloudinaryService $cloudinary)
    {
        $data = $request->validate([
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'caption' => 'nullable|string|max:255',
            'image' => 'required|image|max:5120',
        ]);

        $url = $cloudinary->upload($request->file('image'), 'legitimacy');

        $maxSort = LegitimacyProof::where('transaction_category_id', $data['transaction_category_id'])->max('sort_order');

        $proof = LegitimacyProof::create([
            'transaction_category_id' => $data['transaction_category_id'],
            'caption' => $data['caption'] ?? null,
            'image_path' => $url,
            'sort_order' => ($maxSort ?? -1) + 1,
        ]);

        return response()->json($proof, 201);
    }

    public function destroy(LegitimacyProof $legitimacyProof, CloudinaryService $cloudinary)
    {
        $cloudinary->deleteByUrl($legitimacyProof->image_path);
        $legitimacyProof->delete();

        return response()->json(['message' => 'Deleted']);
    }
}