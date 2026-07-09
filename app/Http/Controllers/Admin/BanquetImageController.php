<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BanquetImage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class BanquetImageController extends Controller
{
    public function destroy(BanquetImage $banquetImage): Response
    {
        $path = $banquetImage->path;
        $banquetImage->delete();
        Storage::disk('public')->delete($path);

        return response(status: 200);
    }
}
