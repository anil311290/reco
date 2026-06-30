<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ContactSubmissionController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = ContactSubmission::query()->orderBy('created_at', 'desc');

            return DataTables::eloquent($query)
                ->addColumn('status_badge', function ($sub) {
                    $classes = [
                        'new'     => 'bg-primary',
                        'read'    => 'bg-info',
                        'replied' => 'bg-success',
                        'archived' => 'bg-secondary',
                    ];
                    return '<span class="badge ' . ($classes[$sub->status] ?? 'bg-secondary') . '">'
                        . ucfirst($sub->status) . '</span>';
                })
                ->addColumn('actions', function ($sub) {
                    $btn = '<a href="' . route('admin.contacts.show', $sub->id) . '" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-eye"></i></a>';
                    $btn .= '<button class="btn btn-sm btn-outline-danger delete-record" data-url="' . route('admin.contacts.destroy', $sub->id) . '"><i class="bi bi-trash"></i></button>';
                    return $btn;
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        return view('admin.cms.contacts.index');
    }

    public function show(ContactSubmission $contact)
    {
        if ($contact->isNew()) {
            $contact->markAsRead();
        }

        return view('admin.cms.contacts.show', compact('contact'));
    }

    public function update(Request $request, ContactSubmission $contact)
    {
        $validated = $request->validate([
            'status'      => 'required|in:read,replied,archived',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        if ($validated['status'] === 'replied' && $contact->status !== 'replied') {
            $contact->markAsReplied(auth()->id());
            if (isset($validated['admin_notes'])) {
                $contact->update(['admin_notes' => $validated['admin_notes']]);
            }
        } else {
            $contact->update($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Contact submission updated.',
        ]);
    }

    public function destroy(ContactSubmission $contact)
    {
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact submission deleted.',
        ]);
    }

    /**
     * Get counts for badge.
     */
    public function counts()
    {
        return response()->json([
            'new' => ContactSubmission::new()->count(),
            'total' => ContactSubmission::count(),
        ]);
    }
}
