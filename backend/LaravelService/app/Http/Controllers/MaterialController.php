<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MaterialController extends Controller
{
    /**
     * Get materials by course
     * GET /api/courses/{courseId}/materials
     */
    public function byCourse(Request $request, $courseId)
    {
        $materials = Material::where('course_id', $courseId)
            ->with('uploader:id,username,full_name')
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($materials->map(fn($m) => $this->formatMaterialResponse($m)));
    }

    /**
     * Get materials by lesson
     * GET /api/lessons/{lessonId}/materials
     */
    public function byLesson(Request $request, $lessonId)
    {
        $materials = Material::where('lesson_id', $lessonId)
            ->with('uploader:id,username,full_name')
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($materials->map(fn($m) => $this->formatMaterialResponse($m)));
    }

    /**
     * Get course materials without lesson (general materials)
     * GET /api/courses/{courseId}/materials/general
     */
    public function generalMaterials(Request $request, $courseId)
    {
        $materials = Material::where('course_id', $courseId)
            ->whereNull('lesson_id')
            ->with('uploader:id,username,full_name')
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($materials->map(fn($m) => $this->formatMaterialResponse($m)));
    }

    /**
     * Upload material
     * POST /api/materials/upload
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'courseId' => 'required|integer|exists:courses,id',
            'lessonId' => 'nullable|integer|exists:lessons,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fileUrl' => 'nullable|string|max:1000',
            'type' => 'required|string|in:PDF,DOC,DOCX,PPT,PPTX,TXT,HTML,IMAGE,VIDEO,MP4,AVI,MOV,OTHER',
            'driveFileId' => 'nullable|string',
            'driveEmbedLink' => 'nullable|string|max:500',
            'driveDownloadLink' => 'nullable|string|max:500',
            'fileSize' => 'nullable|integer',
            'originalFilename' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = $request->user();

        $material = Material::create([
            'course_id' => $request->courseId,
            'lesson_id' => $request->lessonId,
            'title' => $request->title,
            'description' => $request->description,
            'file_url' => $request->fileUrl,
            'type' => $request->type,
            'drive_file_id' => $request->driveFileId,
            'drive_embed_link' => $request->driveEmbedLink,
            'drive_download_link' => $request->driveDownloadLink,
            'file_size' => $request->fileSize,
            'original_filename' => $request->originalFilename,
            'uploaded_by' => $user->id,
        ]);

        return response()->json($this->formatMaterialResponse($material), 201);
    }

    /**
     * Delete material
     * DELETE /api/materials/{id}
     */
    public function destroy(Request $request, $id)
    {
        $material = Material::findOrFail($id);
        $material->delete();

        return response()->json(['message' => 'Xóa tài liệu thành công']);
    }

    private function formatMaterialResponse(Material $material): array
    {
        return [
            'id' => $material->id,
            'courseId' => $material->course_id,
            'lessonId' => $material->lesson_id,
            'title' => $material->title,
            'description' => $material->description,
            'fileUrl' => $material->file_url,
            'type' => $material->type,
            'driveFileId' => $material->drive_file_id,
            'driveEmbedLink' => $material->drive_embed_link,
            'driveDownloadLink' => $material->drive_download_link,
            'fileSize' => $material->file_size,
            'originalFilename' => $material->original_filename,
            'uploadedBy' => $material->uploaded_by,
            'uploaderName' => $material->uploader?->full_name ?? $material->uploader?->username,
            'uploadedAt' => $material->uploaded_at?->toISOString(),
        ];
    }
}
