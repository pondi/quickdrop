<?php

namespace App\Http\Controllers;

use App\Models\FileTypeSetting;
use App\Services\FileTypeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackstageFileTypesController extends Controller
{
    public function __construct(
        private readonly FileTypeService $fileTypeService
    ) {
    }

    public function index(Request $request): Response
    {
        $query = FileTypeSetting::query();
        
        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('extension', 'like', "%{$search}%")
                  ->orWhere('mime_type', 'like', "%{$search}%")
                  ->orWhere('display_name', 'like', "%{$search}%");
            });
        }
        
        // Filter by category
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }
        
        // Filter by status
        if ($request->has('is_allowed')) {
            $query->where('is_allowed', $request->boolean('is_allowed'));
        }
        
        $fileTypes = $query->orderBy('category')
                           ->orderBy('priority', 'desc')
                           ->orderBy('extension')
                           ->paginate(20)
                           ->withQueryString();
        
        return Inertia::render('Backstage/FileTypes/Index', [
            'fileTypes' => $fileTypes,
            'filters' => $request->only(['search', 'category', 'is_allowed']),
            'categories' => FileTypeSetting::CATEGORIES,
            'stats' => FileTypeSetting::getCategoryStats(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Backstage/FileTypes/Create', [
            'categories' => FileTypeSetting::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'extension' => 'required|string|max:10|unique:file_type_settings',
            'mime_type' => 'required|string|max:100',
            'display_name' => 'required|string|max:50',
            'category' => 'required|string|in:' . implode(',', array_keys(FileTypeSetting::CATEGORIES)),
            'is_allowed' => 'boolean',
            'max_size' => 'nullable|integer|min:0',
            'icon_class' => 'nullable|string|max:50',
            'priority' => 'integer',
        ]);
        
        $fileType = $this->fileTypeService->createFileType($validated);
        
        return redirect()->route('backstage.file-types.index')
            ->with('success', 'File type created successfully.');
    }

    public function edit(FileTypeSetting $fileType): Response
    {
        return Inertia::render('Backstage/FileTypes/Edit', [
            'fileType' => $fileType,
            'categories' => FileTypeSetting::CATEGORIES,
        ]);
    }

    public function update(Request $request, FileTypeSetting $fileType)
    {
        $validated = $request->validate([
            'mime_type' => 'required|string|max:100',
            'display_name' => 'required|string|max:50',
            'category' => 'required|string|in:' . implode(',', array_keys(FileTypeSetting::CATEGORIES)),
            'is_allowed' => 'boolean',
            'max_size' => 'nullable|integer|min:0',
            'icon_class' => 'nullable|string|max:50',
            'priority' => 'integer',
        ]);
        
        $this->fileTypeService->updateFileType($fileType, $validated);
        
        return redirect()->route('backstage.file-types.index')
            ->with('success', 'File type updated successfully.');
    }

    public function toggle(FileTypeSetting $fileType)
    {
        $this->fileTypeService->toggleFileType($fileType);
        
        return back()->with('success', 'File type status updated.');
    }

    public function bulkToggle(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:' . implode(',', array_keys(FileTypeSetting::CATEGORIES)),
            'is_allowed' => 'required|boolean',
        ]);
        
        $count = $this->fileTypeService->bulkToggleCategory(
            $validated['category'],
            $validated['is_allowed']
        );
        
        $action = $validated['is_allowed'] ? 'enabled' : 'disabled';
        
        return back()->with('success', "{$count} file types {$action}.");
    }

    public function destroy(FileTypeSetting $fileType)
    {
        $fileType->delete();
        
        return redirect()->route('backstage.file-types.index')
            ->with('success', 'File type deleted successfully.');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:' . implode(',', array_keys(FileTypeSetting::CATEGORIES)),
            'updates' => 'required|array',
            'updates.is_allowed' => 'sometimes|boolean',
            'updates.max_size' => 'sometimes|nullable|integer|min:0',
        ]);
        
        $count = FileTypeSetting::where('category', $validated['category'])
            ->update($validated['updates']);
        
        return back()->with('success', "{$count} file types updated.");
    }

    public function config()
    {
        return response()->json($this->fileTypeService->getConfigForFrontend());
    }
}
