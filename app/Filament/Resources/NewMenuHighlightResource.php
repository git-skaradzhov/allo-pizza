<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewMenuHighlightResource\Pages;
use App\Models\NewMenuHighlight;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class NewMenuHighlightResource extends Resource
{
    protected static ?string $model = NewMenuHighlight::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Маркетинг';

    protected static ?string $navigationLabel = 'Ново в менюто';

    protected static ?string $modelLabel = 'секция ново в менюто';

    protected static ?string $pluralModelLabel = 'ново в менюто';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Как работи')
                    ->schema([
                        Forms\Components\Placeholder::make('usage_hint')
                            ->label('Управление')
                            ->content(new HtmlString(
                                'Изберете кои продукти да се показват на отделната страница <strong>/novo-v-menuto</strong>. '
                                .'Можете да ги отбелязвате и от <strong>Каталог → Продукти</strong> с опцията '
                                .'<strong>„Ново в менюто“</strong>. Продуктите с етикет <strong>Нов</strong> се показват най-отгоре в списъка. '
                                .'Текстът и SEO се редактират от <strong>Съдържание → Страници → novo-v-menuto</strong>. '
                                .'<a href="'.route('new-menu.index').'" target="_blank" class="text-primary-600 underline">Виж на сайта</a>'
                            ))
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                Forms\Components\Section::make('Основна информация')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заглавие')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('message')
                            ->label('Съобщение')
                            ->rows(2)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активно')
                            ->helperText('Показва страницата /novo-v-menuto и линка в навигацията, когато има избрани активни продукти.')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Подредба')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Продукти в секцията')
                    ->schema([
                        Forms\Components\CheckboxList::make('products')
                            ->label('Продукти')
                            ->relationship(
                                name: 'products',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query
                                    ->with('category')
                                    ->where('products.is_active', true)
                                    ->orderByDesc('products.is_new')
                                    ->orderBy('products.sort_order'),
                            )
                            ->getOptionLabelFromRecordUsing(function (Product $record): string {
                                $prefix = $record->is_new ? '✨ ' : '';

                                return $prefix.$record->category?->name.' – '.$record->name.' ('.money((float) $record->base_price).')';
                            })
                            ->columns(2)
                            ->searchable()
                            ->bulkToggleable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Заглавие')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Продукти')
                    ->counts('products')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Подредба')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активно'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Виж на сайта')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): string => route('new-menu.index'))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewMenuHighlights::route('/'),
            'create' => Pages\CreateNewMenuHighlight::route('/create'),
            'edit' => Pages\EditNewMenuHighlight::route('/{record}/edit'),
        ];
    }
}
