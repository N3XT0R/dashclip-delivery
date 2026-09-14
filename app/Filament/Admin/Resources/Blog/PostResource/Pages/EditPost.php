<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Blog\PostResource\Pages;

use App\Filament\Admin\Resources\Blog\PostResource;
use App\Application\Blog\DuplicatePostUseCase;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    /** @return array<Action> Authorized editorial actions on the saved article. */
    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('duplicate')->label(__('blog.duplicate'))->authorize('replicate')
                ->action(function (): void {
                    $copy = app(DuplicatePostUseCase::class)->execute($this->getRecord());
                    $this->redirect(PostResource::getUrl('edit', ['record' => $copy]));
                }),
            DeleteAction::make(),
        ];
        foreach ($this->getRecord()->translations as $translation) {
            $actions[] = Action::make('preview_'.$translation->locale)->label(__('blog.preview_action').' '.strtoupper($translation->locale))
                ->url(route('blog.preview', $translation))->openUrlInNewTab()->authorize('update');
        }
        return $actions;
    }
}
