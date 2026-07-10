<?php

namespace App\Filament\Resources\NewMenuHighlightResource\Pages;

use App\Filament\Resources\NewMenuHighlightResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNewMenuHighlight extends EditRecord
{
    protected static string $resource = NewMenuHighlightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
