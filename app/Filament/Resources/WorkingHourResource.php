<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkingHourResource\Pages;
use App\Models\WorkingHour;
use App\Support\DayOfWeek;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkingHourResource extends Resource
{
    protected static ?string $model = WorkingHour::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Настройки';

    protected static ?string $modelLabel = 'работно време';

    protected static ?string $pluralModelLabel = 'работно време';

    protected static ?int $navigationSort = 2;

    protected static array $daysOfWeek = DayOfWeek::ISO_LABELS;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('day_of_week')
                            ->label('Ден от седмицата')
                            ->options(DayOfWeek::isoOptions())
                            ->required()
                            ->native(false)
                            ->helperText('Използва се ISO формат: 1 = понеделник, 7 = неделя.'),
                        Forms\Components\TimePicker::make('opens_at')
                            ->label('Отваря')
                            ->seconds(false),
                        Forms\Components\TimePicker::make('closes_at')
                            ->label('Затваря')
                            ->seconds(false),
                        Forms\Components\Toggle::make('is_closed')
                            ->label('Затворено')
                            ->default(false)
                            ->live(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')
                    ->label('Ден')
                    ->formatStateUsing(fn (int $state): string => static::$daysOfWeek[$state] ?? (string) $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('opens_at')
                    ->label('Отваря')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('closes_at')
                    ->label('Затваря')
                    ->time('H:i'),
                Tables\Columns\IconColumn::make('is_closed')
                    ->label('Затворено')
                    ->boolean(),
            ])
            ->defaultSort('day_of_week')
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkingHours::route('/'),
            'create' => Pages\CreateWorkingHour::route('/create'),
            'edit' => Pages\EditWorkingHour::route('/{record}/edit'),
        ];
    }
}
