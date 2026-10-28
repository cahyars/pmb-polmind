<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brochure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrochureController extends Controller
{
    public function index()
    {
        $brochures = Brochure::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.brochures.index', compact('brochures'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(fileRequired: true), [], $this->attributes());

        Brochure::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ] + $this->storeFile($request->file('file')));

        return back()->with('success', 'Brosur "' . $validated['title'] . '" berhasil diunggah dan tampil di landing page.');
    }

    public function update(Request $request, Brochure $brochure)
    {
        $validated = $request->validate($this->rules(fileRequired: false), [], $this->attributes());

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ];

        if ($request->hasFile('file')) {
            $oldPath = $brochure->file_path;
            $data += $this->storeFile($request->file('file'));
        }

        $brochure->update($data);

        if (isset($oldPath)) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'Brosur "' . $brochure->title . '" berhasil diperbarui.');
    }

    public function toggle(Brochure $brochure)
    {
        $brochure->update(['is_active' => ! $brochure->is_active]);

        return back()->with('success', 'Brosur "' . $brochure->title . '" ' . ($brochure->is_active ? 'ditampilkan.' : 'disembunyikan dari landing page.'));
    }

    public function destroy(Brochure $brochure)
    {
        Storage::disk('local')->delete($brochure->file_path);
        $brochure->delete();

        return back()->with('success', 'Brosur "' . $brochure->title . '" berhasil dihapus.');
    }

    public function file(Brochure $brochure)
    {
        abort_unless(Storage::disk('local')->exists($brochure->file_path), 404);

        return Storage::disk('local')->response($brochure->file_path, $brochure->file_name, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function storeFile(UploadedFile $file): array
    {
        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        $filename = now()->format('YmdHis') . '_' . Str::random(8) . '.' . $extension;

        return [
            'file_path' => $file->storeAs(Brochure::FOLDER, $filename, 'local'),
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    private function rules(bool $fileRequired): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'file' => [$fileRequired ? 'required' : 'nullable', 'file', 'mimes:' . implode(',', Brochure::ALLOWED_EXTENSIONS), 'max:' . Brochure::MAX_KB],
        ];
    }

    private function attributes(): array
    {
        return [
            'title' => 'judul brosur',
            'description' => 'keterangan',
            'sort_order' => 'urutan',
            'file' => 'file brosur',
        ];
    }
}
