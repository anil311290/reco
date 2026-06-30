<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\WebsiteService;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function __construct(
        protected WebsiteService $websiteService
    ) {}

    public function index()
    {
        $faqs = Faq::orderBy('sort_order')->get();
        return view('admin.cms.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return view('admin.cms.faqs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'question'   => 'required|string|max:500',
            'answer'     => 'required|string|max:5000',
            'category'   => 'nullable|string|max:100',
            'sort_order' => 'integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['created_by_ip'] = request()->ip();

        Faq::create($validated);
        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully.',
        ]);
    }

    public function edit(Faq $faq)
    {
        return view('admin.cms.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq)
    {
        $validated = $request->validate([
            'question'   => 'required|string|max:500',
            'answer'     => 'required|string|max:5000',
            'category'   => 'nullable|string|max:100',
            'sort_order' => 'integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['updated_by_ip'] = request()->ip();

        $faq->update($validated);
        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully.',
        ]);
    }

    public function destroy(Faq $faq)
    {
        $faq->delete();
        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully.',
        ]);
    }

    public function toggleStatus(Faq $faq)
    {
        $faq->update([
            'is_active' => !$faq->is_active,
            'updated_by' => auth()->id(),
            'updated_by_ip' => request()->ip(),
        ]);

        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'FAQ status updated.',
            'is_active' => $faq->is_active,
        ]);
    }
}
