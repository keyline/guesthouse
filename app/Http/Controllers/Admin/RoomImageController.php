<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoomImage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class RoomImageController extends Controller
{
    public function destroy(RoomImage $roomImage): Response
    {
        $path = $roomImage->path;
        $roomImage->delete();
        Storage::disk('public')->delete($path);

        return response(status: 200);
    }
}
