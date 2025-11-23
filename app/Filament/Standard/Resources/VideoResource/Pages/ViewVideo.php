<?php

namespace App\Filament\Standard\Resources\VideoResource\Pages;

use App\Filament\Standard\Resources\VideoResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewVideo extends ViewRecord
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function resolveRecord(int|string $key): Model
    {
        return static::getModel()::query()
            ->select('videos.*')
            ->join('clips', 'videos.id', '=', 'clips.video_id')
            ->where('clips.user_id', auth()->id())
            ->where('videos.id', $key)
            ->firstOrFail();
    }
}
