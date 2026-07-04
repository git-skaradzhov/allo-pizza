<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\ProductImage;
use App\Services\ProductImageProcessor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Галерия';

    protected static ?string $modelLabel = 'изображение';

    protected static ?string $pluralModelLabel = 'изображения';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('image')
                    ->label('Изображение')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->required()
                    ->directory('products')
                    ->disk('public')
                    ->visibility('public')
                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, ?ProductImage $record): string {
                        $product = $this->getOwnerRecord();
                        $processor = app(ProductImageProcessor::class);
                        $existingPath = $record?->exists ? $record->image : null;
                        $index = $existingPath
                            ? ($processor->extractGalleryIndex($existingPath, $product->slug) ?? $processor->nextGalleryIndex($product))
                            : $processor->nextGalleryIndex($product);

                        return $processor->storeGallery($product, $index, $file, $existingPath);
                    })
                    ->deleteUploadedFileUsing(fn (?string $file) => app(ProductImageProcessor::class)->deleteVariants($file))
                    ->helperText('JPG, PNG или WebP. Минимум 1024×1024 px. Запазват се автоматично като WebP.'),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Подредба')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('image')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Изображение')
                    ->disk('public')
                    ->visibility('public')
                    ->getStateUsing(fn (ProductImage $record): ?string => product_image_storage_path($record->image, 'small')),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Подредба'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
