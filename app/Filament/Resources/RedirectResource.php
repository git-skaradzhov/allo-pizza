<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectResource\Pages;
use App\Models\Redirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RedirectResource extends Resource
{
    protected static ?string $model = Redirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-right';

    protected static ?string $navigationGroup = 'Настройки';

    protected static ?string $modelLabel = 'пренасочване';

    protected static ?string $pluralModelLabel = 'пренасочвания';

    protected static ?string $navigationLabel = 'Пренасочвания';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('from_path')
                    ->label('От път')
                    ->required()
                    ->prefix('/')
                    ->helperText('Пример: /stara-stranica'),
                Forms\Components\TextInput::make('to_path')
                    ->label('Към')
                    ->required()
                    ->helperText('Пример: /pages/za-nas или пълен URL'),
                Forms\Components\Select::make('status_code')
                    ->label('HTTP код')
                    ->options([
                        301 => '301 Permanent',
                        302 => '302 Temporary',
                        307 => '307 Temporary',
                        308 => '308 Permanent',
                    ])
                    ->default(301)
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Активно')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from_path')
                    ->label('От')
                    ->searchable(),
                Tables\Columns\TextColumn::make('to_path')
                    ->label('Към')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_code')
                    ->label('Код')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRedirects::route('/'),
        ];
    }
}
