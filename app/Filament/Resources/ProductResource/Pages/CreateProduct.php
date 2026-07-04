<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Concerns\ProcessesProductImageUpload;
use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use ProcessesProductImageUpload;

    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->processProductImageInFormData($data, new Product($data));
    }
}
