<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FastContentController extends Controller
{
    /**
     * Listar contenido rápido del usuario autenticado
     */
    public function index(Request $request)
    {
        $contents = \App\Models\FastContent::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($contents);
    }

    /**
     * Crear nuevo contenido rápido (Subir foto/video)
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov|max:20480', // Max 20MB
            'price' => 'required|integer|min:0',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $file = $request->file('file');

        // Determinar tipo
        $mime = $file->getMimeType();
        $type = str_contains($mime, 'video') ? 'video' : 'image';

        // Subir a Cloudinary o Storage local (Usaremos Storage local publico por ahora)
        // Guardar en 'public/fast_content'
        $path = $file->store('fast_content', 'public');
        $url = asset('storage/' . $path);

        $content = \App\Models\FastContent::create([
            'user_id' => $user->id,
            'type' => $type,
            'url' => $url,
            'price' => $request->price,
            'description' => $request->description,
        ]);

        return response()->json($content, 201);
    }

    /**
     * Eliminar contenido
     */
    public function destroy(Request $request, $id)
    {
        $content = \App\Models\FastContent::where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        // Eliminar archivo (Opcional, si queremos limpiar disco)
        // ...

        $content->delete();

        return response()->json(['message' => 'Contenido eliminado']);
    }
}
