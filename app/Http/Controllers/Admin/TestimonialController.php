<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\WebsiteService;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function __construct(
        protected WebsiteService $websiteService
    ) {}

    public function index()
    {
        $testimonials = Testimonial::orderBy('sort_order')->get();
        return view('admin.cms.testimonials.index', compact('testimonials'));
    }

    public function create()
    {
        return view('admin.cms.testimonials.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name'  => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'designation'  => 'nullable|string|max:255',
            'testimonial'  => 'required|string|max:2000',
            'rating'       => 'required|integer|min:1|max:5',
            'is_featured'  => 'boolean',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer|min:0',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['created_by_ip'] = request()->ip();

        Testimonial::create($validated);
        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Testimonial created successfully.',
        ]);
    }

    public function edit(Testimonial $testimonial)
    {
        return view('admin.cms.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'client_name'  => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'designation'  => 'nullable|string|max:255',
            'testimonial'  => 'required|string|max:2000',
            'rating'       => 'required|integer|min:1|max:5',
            'is_featured'  => 'boolean',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer|min:0',
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['updated_by_ip'] = request()->ip();

        $testimonial->update($validated);
        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Testimonial updated successfully.',
        ]);
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();
        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Testimonial deleted successfully.',
        ]);
    }

    public function toggleStatus(Testimonial $testimonial)
    {
        $testimonial->update([
            'is_active' => !$testimonial->is_active,
            'updated_by' => auth()->id(),
            'updated_by_ip' => request()->ip(),
        ]);

        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Testimonial status updated.',
            'is_active' => $testimonial->is_active,
        ]);
    }
}
