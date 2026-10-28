<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Services\AuditService;
use App\Support\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
<<<<<<< HEAD
use Illuminate\Support\Facades\DB;
=======
>>>>>>> origin/main
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $banners = Banner::when($request->input('location'), fn ($q, $l) => $q->where('location', $l))
<<<<<<< HEAD
            ->when($request->input('q'), function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('heading', 'like', "%{$term}%");
                });
            })
=======
>>>>>>> origin/main
            ->orderBy('location')
            ->orderBy('sort_order')
            ->paginate(20);

        return view('admin.banners.index', [
            'banners'   => $banners,
            'locations' => $this->locations(),
<<<<<<< HEAD
            'filters'   => $request->only(['location', 'q']),
            'stats'     => [
                'published'  => Banner::where('is_published', true)->count(),
                'scheduled'  => Banner::where('is_published', true)->whereDate('starts_at', '>', now())->count(),
                'total'      => Banner::count(),
                'impressions' => (int) Banner::sum('impressions'),
                'clicks'     => (int) Banner::sum('clicks'),
            ],
=======
            'filters'   => $request->only(['location']),
>>>>>>> origin/main
        ]);
    }

    public function create(): View
    {
        return view('admin.banners.create', ['locations' => $this->locations()]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateBanner($request);

        if (empty($validated['image']) && ! $request->hasFile('image_file')) {
            return back()->withErrors(['image' => 'An image URL or upload is required.'])->withInput();
        }

        if ($request->hasFile('image_file')) {
            $validated['image'] = ImageCompressor::store($request->file('image_file'), 'banners');
        }
        if ($request->hasFile('mobile_image_file')) {
            $validated['mobile_image'] = ImageCompressor::store($request->file('mobile_image_file'), 'banners');
        }

        unset($validated['image_file'], $validated['mobile_image_file']);

        $validated['configuration'] = $this->decodeJson($request->input('configuration'));

        $banner = Banner::create($validated);
        $this->audit->record('banners.create', $banner);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created.');
    }

    public function show(Banner $banner)
    {
        return redirect()->route('admin.banners.edit', $banner);
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', [
            'banner'    => $banner,
            'locations' => $this->locations(),
        ]);
    }

    public function update(Request $request, Banner $banner)
    {
        $validated = $this->validateBanner($request);

        if (empty($validated['image']) && ! $banner->image && ! $request->hasFile('image_file')) {
            return back()->withErrors(['image' => 'An image URL or upload is required.'])->withInput();
        }

        if ($request->hasFile('image_file')) {
            $validated['image'] = ImageCompressor::store($request->file('image_file'), 'banners');
        } elseif (empty($validated['image'])) {
            $validated['image'] = $banner->image;
        }
        if ($request->hasFile('mobile_image_file')) {
            $validated['mobile_image'] = ImageCompressor::store($request->file('mobile_image_file'), 'banners');
        } elseif (empty($validated['mobile_image']) && $banner->mobile_image) {
            // keep existing if field cleared? keep existing
            $validated['mobile_image'] = $banner->mobile_image;
        }

        unset($validated['image_file'], $validated['mobile_image_file']);

        $validated['configuration'] = $this->decodeJson($request->input('configuration'));

        $banner->update($validated);
        $this->audit->record('banners.update', $banner);

        return back()->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        $this->audit->record('banners.delete', $banner);
        $banner->delete();
        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted.');
    }

<<<<<<< HEAD
    // --------------------------------------------------------------------------
    // Bulk & ordering
    // --------------------------------------------------------------------------
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:100'],
            'ids.*'  => ['integer', 'exists:banners,id'],
            'action' => ['required', 'in:publish,unpublish,delete'],
        ]);

        $banners = Banner::whereIn('id', $validated['ids'])->get();

        foreach ($banners as $banner) {
            match ($validated['action']) {
                'publish'   => $banner->update(['is_published' => true]),
                'unpublish' => $banner->update(['is_published' => false]),
                'delete'    => (function () use ($banner) {
                    $this->audit->record('banners.bulk.delete', $banner);
                    $banner->delete();
                })(),
            };
        }

        $this->audit->record('banners.bulk.update', null, [], [
            'action' => $validated['action'],
            'ids'    => $validated['ids'],
        ]);

        return back()->with('success', count($validated['ids']).' banner(s) updated.');
    }

    public function move(Banner $banner, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        // Only move within the banner's own location group.
        $siblings = Banner::where('location', $banner->location)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $index = $siblings->search(fn (Banner $sibling) => $sibling->id === $banner->id);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $target < 0 || $target >= $siblings->count()) {
            return back()->with('success', 'Banner is already at the edge of its placement.');
        }

        $ordered = $siblings->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $position => $sibling) {
                if ((int) $sibling->sort_order !== $position) {
                    $sibling->update(['sort_order' => $position]);
                }
            }
        });

        $this->audit->record('banners.reorder', $banner, null, ['direction' => $direction]);

        return back()->with('success', 'Banner order updated.');
    }

    public function moveUp(Banner $banner): RedirectResponse
    {
        return $this->move($banner, 'up');
    }

=======
>>>>>>> origin/main
    public function uploadImage(Request $request, Banner $banner): RedirectResponse
    {
        // Check for PHP upload errors directly from $_FILES
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $errorCode = $_FILES['image_file']['error'];
            if ($errorCode !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit (' . ini_get('upload_max_filesize') . '). Try a smaller file or contact your host.',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
                ];
                return back()->withErrors(['image_file' => $errorMessages[$errorCode] ?? 'Upload failed (error code: ' . $errorCode . ').'])->withInput();
            }
        }

        $validated = $request->validate([
            'image_file' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'target'     => 'nullable|in:image,mobile_image',
        ]);

        $field = ($validated['target'] ?? 'image') === 'mobile_image' ? 'mobile_image' : 'image';
        $banner->update([$field => ImageCompressor::store($request->file('image_file'), 'banners')]);
        $this->audit->record('banners.image.upload', $banner);

        return back()->with('success', 'Image uploaded.');
    }

    private function validateBanner(Request $request): array
    {
        // Check for PHP upload errors directly from $_FILES (hasFile() returns false on upload errors)
        $uploadErrors = [];
        foreach (['image_file', 'mobile_image_file'] as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $errorCode = $_FILES[$field]['error'];
                if ($errorCode !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit (' . ini_get('upload_max_filesize') . '). Try a smaller file or contact your host.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
                    ];
                    $uploadErrors[$field] = $errorMessages[$errorCode] ?? 'Upload failed (error code: ' . $errorCode . ').';
                }
            }
        }

        if (! empty($uploadErrors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($uploadErrors);
        }

        return $request->validate([
            'name'         => 'required|string|max:120',
            'location'     => 'required|string|max:60',
            'image'        => 'nullable|string|max:300',
            'image_file'   => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'mobile_image' => 'nullable|string|max:300',
            'mobile_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'link_url'     => 'nullable|string|max:300',
            'link_text'    => 'nullable|string|max:80',
            'heading'      => 'nullable|string|max:200',
            'subheading'   => 'nullable|string|max:300',
            'sort_order'   => 'sometimes|integer|min:0',
<<<<<<< HEAD
            'alt_text'     => 'nullable|string|max:300',
=======
>>>>>>> origin/main
            'is_published' => 'sometimes|boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date|after_or_equal:starts_at',
        ]);
    }

    private function locations(): array
    {
        return ['hero', 'homepage_middle', 'category_top', 'collection_top', 'sidebar', 'popup', 'auth'];
    }

    private function decodeJson(?string $json): ?array
    {
        if (! $json) {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
