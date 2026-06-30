<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsitePage;
use App\Services\WebsiteService;
use Illuminate\Http\Request;

class CmsPageController extends Controller
{
    public function __construct(
        protected WebsiteService $websiteService
    ) {}

    public function index()
    {
        $pages = WebsitePage::orderBy('nav_order')->get();
        return view('admin.cms.pages.index', compact('pages'));
    }

    public function edit(WebsitePage $page)
    {
        return view('admin.cms.pages.edit', compact('page'));
    }

    public function update(Request $request, WebsitePage $page)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'meta_title'       => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'content'          => 'nullable|string',
            'template'         => 'required|string|max:50',
            'status'           => 'required|in:draft,published,archived',
            'show_in_nav'      => 'boolean',
            'nav_order'        => 'integer|min:0',
        ]);

        $validated['updated_by'] = auth()->id();
        $validated['updated_by_ip'] = request()->ip();

        $page->update($validated);
        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Page updated successfully.',
        ]);
    }

    public function toggleNav(WebsitePage $page)
    {
        $page->update([
            'show_in_nav' => !$page->show_in_nav,
            'updated_by' => auth()->id(),
            'updated_by_ip' => request()->ip(),
        ]);

        $this->websiteService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Navigation visibility updated.',
            'show_in_nav' => $page->show_in_nav,
        ]);
    }
}
