<?php

namespace App\Http\Controllers;

use App\Livewire\Concerns\WithRichText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Приём картинок из редактора Summernote.
 *
 * Раньше вставленная картинка оставалась в HTML как data:image и уезжала на
 * сервер в теле Livewire-запроса при каждом сохранении: пара фотографий
 * превращала форму в мегабайты base64 и упиралась в лимиты запроса. Теперь
 * файл уходит отдельным запросом сразу при вставке, а в тексте остаётся
 * обычная ссылка.
 */
class RichTextImageController extends Controller
{
    use WithRichText;

    /** Куда разрешено загружать: произвольную папку принимать нельзя. */
    private const ALLOWED_FOLDERS = ['articles', 'products', 'categories', 'solutions', 'projects', 'services', 'offers'];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'folder' => 'nullable|string',
        ]);

        $folder = $data['folder'] ?? 'articles';

        if (! in_array($folder, self::ALLOWED_FOLDERS, true)) {
            return response()->json(['message' => 'Недопустимая папка загрузки.'], 422);
        }

        $file = $request->file('file');
        $dir = $this->inlineImageDir($folder);

        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return response()->json(['message' => 'Не удалось создать каталог для загрузки.'], 500);
        }

        $name = $this->inlineImageName($file->getClientOriginalExtension() ?: 'jpg');
        $file->move($dir, $name);

        return response()->json(['url' => $this->inlineImageUrl($folder, $name)]);
    }
}
