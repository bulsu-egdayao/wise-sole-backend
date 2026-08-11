<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index()
    {
        return Inquiry::with('product')->latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'name' => 'required|string|max:255',
            'contact' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        $inquiry = Inquiry::create([
            ...$validated,
            'status' => 'new',
        ]);

        return response()->json($inquiry, 201);
    }

    public function update(Request $request, Inquiry $inquiry)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,viewed,responded',
        ]);

        $inquiry->update($validated);

        return response()->json($inquiry);
    }
}