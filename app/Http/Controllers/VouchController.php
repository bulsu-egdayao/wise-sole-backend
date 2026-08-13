<?php

namespace App\Http\Controllers;

use App\Models\Vouch;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class VouchController extends Controller
{
    // Public: only approved vouches, newest first
    public function index()
    {
        return Vouch::where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Admin: all vouches regardless of status, for the moderation queue
    public function adminIndex()
    {
        return Vouch::orderBy('created_at', 'desc')->get();
    }

    // Public: anyone can submit a vouch, but it starts as "pending". Photo is optional.
    public function store(Request $request, CloudinaryService $cloudinary)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'message' => 'required|string|max:1000',
            'image' => 'nullable|image|max:5120',
        ]);

        $data['status'] = 'pending';

        if ($request->hasFile('image')) {
            $data['image_path'] = $cloudinary->upload($request->file('image'), 'vouches');
        }

        unset($data['image']);

        $vouch = Vouch::create($data);

        return response()->json($vouch, 201);
    }

    // Admin: approve or reject
    public function updateStatus(Request $request, Vouch $vouch)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $vouch->update($data);

        return response()->json($vouch);
    }

    public function destroy(Vouch $vouch, CloudinaryService $cloudinary)
    {
        if ($vouch->image_path) {
            $cloudinary->deleteByUrl($vouch->image_path);
        }

        $vouch->delete();

        return response()->json(['message' => 'Deleted']);
    }
}