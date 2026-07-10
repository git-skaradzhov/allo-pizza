<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LunchMenuResource\Pages;
use App\Models\LunchMenu;
use App\Models\LunchMenuItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class LunchMenuResource extends Resource
{
    protected static ?string $model = LunchMenu::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Маркетинг';

    protected static ?string $modelLabel = 'обедно меню';

    protected static ?string $pluralModelLabel = 'обедни менюта';

    protected static ?int $navigationSort = 2;

    protected static array $daysOfWeek = [
        1 => 'Понеделник',
        2 => 'Вторник',
        3 => 'Сряда',
        4 => 'Четвъртък',
        5 => 'Петък',
        6 => 'Събота',
        7 => 'Неделя',
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Как работи')
                    ->schema([
                        Forms\Components\Placeholder::make('public_page_hint')
                            ->label('Публична страница')
                            ->content(new HtmlString(
                                'Първо създайте артикули в <strong>Обедни артикули</strong>. '
                                .'Тук избирате кои от тях участват в това меню. '
                                .'Текстът и SEO се редактират от <strong>Съдържание → Страници → obedno-menyu</strong>. '
                                .'<a href="'.route('lunch.index').'" target="_blank" class="text-primary-600 underline">Виж на сайта</a>'
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
                    ]),
                Forms\Components\Section::make('График')
                    ->schema([
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Начален час')
                            ->required()
                            ->seconds(false),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('Краен час')
                            ->required()
                            ->seconds(false),
                        Forms\Components\CheckboxList::make('days_of_week')
                            ->label('Дни от седмицата')
                            ->options(static::$daysOfWeek)
                            ->columns(2)
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Активно')
                            ->helperText('Показва обедното меню в навигацията, във футъра и на началната страница.')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Подредба')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Артикули в менюто')
                    ->schema([
                        Forms\Components\CheckboxList::make('items')
                            ->label('Обедни артикули')
                            ->relationship(
                                name: 'items',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query
                                    ->where('lunch_menu_items.is_active', true)
                                    ->orderBy('lunch_menu_items.section')
                                    ->orderBy('lunch_menu_items.sort_order'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (LunchMenuItem $record): string => $record->section.' – '.$record->name.' ('.money($record->price).')')
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
                Tables\Columns\TextColumn::make('start_time')
                    ->label('От')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('До')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('days_of_week')
                    ->label('Дни')
                    ->formatStateUsing(function ($state, LunchMenu $record): string {
                        $days = $record->days_of_week;

                        if (empty($days)) {
                            return '-';
                        }

                        return collect($days)
                            ->map(fn ($day) => static::$daysOfWeek[(int) $day] ?? $day)
                            ->implode(', ');
                    })
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активно')
                    ->boolean(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Артикули')
                    ->counts('items')
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
                    ->url(fn (): string => route('lunch.index'))
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
            'index' => Pages\ListLunchMenus::route('/'),
            'create' => Pages\CreateLunchMenu::route('/create'),
            'edit' => Pages\EditLunchMenu::route('/{record}/edit'),
        ];
    }
}
