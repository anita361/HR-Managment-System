<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FamilyMember;
use Illuminate\Support\Facades\Auth;

class FamilyInformationController extends Controller
{
    // Fetch a single member for editing
    public function getRecord($id)
    {
        $user = auth()->user();
        $member = $user->familyMembers()->find($id);

        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $member]);
    }

    // Save or update a single member (used by modal form)
    public function saveRecord(Request $request)
    {
        // Accept either array 'family' (batch) or single item fields + optional id
        if ($request->has('family')) {
            // keep existing batch logic if you already have it
            $validated = $request->validate([
                'family' => 'required|array|min:1',
                'family.*.id' => 'nullable|integer|exists:family_members,id',
                'family.*.name' => 'required|string|max:191',
                'family.*.relationship' => 'nullable|string|max:191',
                'family.*.dob' => 'nullable|date',
                'family.*.phone' => 'nullable|string|max:20',
            ]);

            $user = auth()->user();
            \DB::transaction(function () use ($user, $validated) {
                foreach ($validated['family'] as $member) {
                    \App\Models\FamilyMember::updateOrCreate(
                        ['id' => $member['id'] ?? null, 'user_id' => $user->id],
                        [
                            'name' => $member['name'],
                            'relationship' => $member['relationship'] ?? null,
                            'dob' => $member['dob'] ?? null,
                            'phone' => $member['phone'] ?? null,
                        ]
                    );
                }
            });

            return response()->json(['success' => true, 'message' => 'Family info saved.']);
        }

        // Single item save (modal)
        $validated = $request->validate([
            'id' => 'nullable|integer|exists:family_members,id',
            'name' => 'required|string|max:191',
            'relationship' => 'nullable|string|max:191',
            'dob' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = auth()->user();

        if (!empty($validated['id'])) {
            $member = $user->familyMembers()->where('id', $validated['id'])->firstOrFail();
            $member->update([
                'name' => $validated['name'],
                'relationship' => $validated['relationship'] ?? null,
                'dob' => $validated['dob'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        } else {
            $member = $user->familyMembers()->create([
                'name' => $validated['name'],
                'relationship' => $validated['relationship'] ?? null,
                'dob' => $validated['dob'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Family member saved.', 'data' => $member]);
    }

    // Delete a member
    public function deleteRecord($id)
    {
        $user = auth()->user();
        $member = $user->familyMembers()->find($id);
        if (!$member) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $member->delete();

        return response()->json(['success' => true, 'message' => 'Deleted.']);
    }
}
