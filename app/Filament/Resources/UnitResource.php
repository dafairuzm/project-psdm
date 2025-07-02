<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitResource\Pages;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\UnitResource\RelationManagers\UsersRelationManager;
use Filament\Pages\SubNavigationPosition;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Pengguna';
    protected static ?string $modelLabel = 'Unit Kerja';
    protected static ?string $pluralModelLabel = 'Unit Kerja';
    protected static ?string $navigationLabel = 'Unit Kerja';
    protected static ?int $navigationSort = 2;
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Unit Kerja')
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->label('Deskripsi'),
            ]);
    }
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make([
                    
                    TextEntry::make('name')
                    ->label('Unit Kerja'),
                    TextEntry::make('description')
                    ->label('Deskripsi'),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nama Unit'),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->label('Deskripsi')
                    ->extraAttributes([
                        'style' => 'width: 400px; max-width: 600px;'
                    ])
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make()
                ->modalActions([
                    Tables\Actions\Action::make('goToEdit')
                        ->label('Edit')
                        ->color('primary')
                        ->icon('heroicon-o-pencil')
                        ->url(fn($record) => static::getUrl('edit', ['record' => $record]))
                        ->openUrlInNewTab(false),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
            ])
            ->recordUrl(null)
            ->recordAction('view');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnit::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
            'view' => Pages\ViewUnit::route('/{record}'),
        ];
    }
    public static function getRelations(): array
    {
    return [
        UsersRelationManager::class,
    ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewUnit::class,
            Pages\EditUnit::class,
        ]);
    }
} 