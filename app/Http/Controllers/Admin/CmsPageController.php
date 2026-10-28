<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
<<<<<<< HEAD
use App\Models\CmsPageRevision;
=======
>>>>>>> origin/main
use App\Services\AuditService;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $pages = CmsPage::with('updatedBy')
            ->when($request->input('q'), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        return view('admin.cms.index', [
            'pages'   => $pages,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('admin.cms.create', [
            'statuses'   => ['draft', 'published', 'scheduled', 'archived'],
            'templates'  => ['default', 'landing', 'legal', 'faq'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePage($request);
        if ($request->hasFile('featured_image_file')) {
            $validated['featured_image'] = ImageCompressor::store($request->file('featured_image_file'), 'cms');
        }
        unset($validated['featured_image_file']);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $validated['sections'] = $this->decodeSections($request->input('sections'));

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $page = CmsPage::create($validated);
        $this->audit->record('cms.create', $page);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page created.');
    }

    public function show(CmsPage $page)
    {
        return redirect()->route('admin.pages.edit', $page);
    }

    public function edit(CmsPage $page): View
    {
        return view('admin.cms.edit', [
            'page'      => $page,
            'statuses'  => ['draft', 'published', 'scheduled', 'archived'],
            'templates' => ['default', 'landing', 'legal', 'faq'],
        ]);
    }

    public function update(Request $request, CmsPage $page)
    {
        $validated = $this->validatePage($request, $page);
        if ($request->hasFile('featured_image_file')) {
            $validated['featured_image'] = ImageCompressor::store($request->file('featured_image_file'), 'cms');
        } elseif (empty($validated['featured_image']) && $page->featured_image) {
            $validated['featured_image'] = $page->featured_image;
        }
        unset($validated['featured_image_file']);
        $validated['updated_by'] = auth()->id();
        $validated['sections'] = $this->decodeSections($request->input('sections'));

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = $page->published_at ?? now();
        }

<<<<<<< HEAD
        // Snapshot the PRE-update state so the change itself is reversible.
        $this->recordRevision($page, auth()->id());
=======
>>>>>>> origin/main
        $page->update($validated);
        $this->audit->record('cms.update', $page);

        return back()->with('success', 'Page updated.');
    }

    public function destroy(CmsPage $page)
    {
        $this->audit->record('cms.delete', $page);
        $page->delete();
        return redirect()->route('admin.pages.index')->with('success', 'Page archived.');
    }

<<<<<<< HEAD
    // --------------------------------------------------------------------------
    // Revisions & preview
    // --------------------------------------------------------------------------

    /** Lists revisions (newest first) for the edit sidebar. */
    public function revisions(CmsPage $page): View
    {
        return view('admin.cms.revisions', [
            'page'      => $page,
            'revisions' => $page->revisions()->with('user:id,name')->latest('id')->paginate(15),
        ]);
    }

    /** Snapshot taken on every successful update. */
    private function recordRevision(CmsPage $page, ?int $userId): void
    {
        $page->revisions()->create([
            'user_id'        => $userId,
            'title'          => $page->title,
            'content'        => $page->content,
            'sections'       => $page->sections,
            'status'         => $page->status,
            'published_at'   => $page->published_at,
            'snapshot'       => collect($page->getAttributes())
                ->only(['title', 'slug', 'template', 'content', 'sections', 'excerpt',
                    'featured_image', 'status', 'published_at', 'seo_title',
                    'seo_description', 'seo_image'])
                ->all(),
        ]);
    }

    /** Roll the page back to a stored revision snapshot. */
    public function restore(Request $request, CmsPage $page, CmsPageRevision $revision)
    {
        // The revision must belong to this page.
        abort_unless($revision->cms_page_id === $page->id, 404);

        // Snapshot the current state first so the rollback itself is reversible.
        $this->recordRevision($page, $request->user()->id);

        $restore = collect($revision->snapshot ?? [])
            ->only(['title', 'slug', 'template', 'content', 'sections', 'excerpt',
                'featured_image', 'status', 'seo_title', 'seo_description', 'seo_image'])
            ->all();

        $page->update($restore + ['updated_by' => $request->user()->id]);
        $this->audit->record('cms.restore', $page, null, ['revision_id' => $revision->id]);

        return redirect()->route('admin.pages.edit', $page)
            ->with('success', "Page restored to revision from {$revision->created_at->format('d M Y H:i')}.");
    }

    /** Render the storefront view for this page (drafts included) for staff. */
    public function preview(CmsPage $page): View
    {
        return view('storefront.cms.show', [
            'page'      => $page,
            'isPreview' => true,
        ]);
    }

=======
>>>>>>> origin/main
    private function validatePage(Request $request, ?CmsPage $page = null): array
    {
        // Check for PHP upload errors directly from $_FILES
        if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errorCode = $_FILES['featured_image_file']['error'];
            if ($errorCode !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit (' . ini_get('upload_max_filesize') . '). Try a smaller file or contact your host.',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
                ];
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'featured_image_file' => $errorMessages[$errorCode] ?? 'Upload failed (error code: ' . $errorCode . ').',
                ]);
            }
        }

        return $request->validate([
            'title'            => 'required|string|max:200',
            'slug'             => ['nullable', 'string', 'max:200', Rule::unique('cms_pages', 'slug')->ignore($page?->id)],
            'template'         => 'required|string|max:40',
            'content'          => 'nullable|string',
            'excerpt'          => 'nullable|string|max:500',
            'featured_image'   => 'nullable|string|max:300',
            'featured_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'status'           => 'required|in:draft,published,scheduled,archived',
            'published_at'     => 'nullable|date',
            'seo_title'        => 'nullable|string|max:200',
            'seo_description'  => 'nullable|string|max:500',
            'seo_image'        => 'nullable|string|max:300',
        ]);
    }

    private function decodeSections(?string $json): ?array
    {
        if (! $json) {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
