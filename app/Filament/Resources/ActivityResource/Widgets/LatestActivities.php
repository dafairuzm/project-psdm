<?php

namespace App\Filament\Resources\ActivityResource\Widgets;

use App\Models\Activity;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;

class LatestActivities   extends BaseWidget
{
    protected static ?string $heading = 'Latest Activities';
    
    protected int | string | array $columnSpan = 'full';
    
    // Refresh setiap 30 detik (opsional)
    protected static ?string $pollingInterval = '30s';
    
    // Jumlah data yang ditampilkan
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->with(['categories', 'users'])
                    ->latest('created_at')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('title')
                    ->label('Nama Kegiatan')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                    
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'primary' => 'inhouse',
                        'warning' => 'exhouse',
                    ]),
                    
                TextColumn::make('categories.name')
                    ->label('Kategori')
                    ->badge()
                    ->separator(',')
                    ->limit(3)
                    ->color('gray'),
                    
                // TextColumn::make('speaker')
                //     ->label('Speaker')
                //     ->limit(30)
                //     ->placeholder('No speaker'),
                    
                TextColumn::make('organizer')
                    ->label('Penyelenggara')
                    ->limit(30)
                    ->placeholder('No organizer'),
                    
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->limit(25)
                    ->placeholder('No location'),
                    
                TextColumn::make('start_date')
                    ->label('Tanggal Mulai')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return null;
                        return \Carbon\Carbon::parse($state)->locale('id')->isoFormat('D MMMM Y');
                    })
                    ->sortable(),
                    
                TextColumn::make('finish_date')
                    ->label('Tanggal Selesai')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return null;
                        return \Carbon\Carbon::parse($state)->locale('id')->isoFormat('D MMMM Y');
                    })
                    ->placeholder('Same day'),
                    
                TextColumn::make('duration')
                    ->label('Durasi')
                    ->suffix(' JPL')
                    ->placeholder('Not set'),
                    
                TextColumn::make('users_count')
                    ->label('Peserta')
                    ->counts('users')
                    ->badge()
                    ->color('success'),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'workshop' => 'Workshop',
                        'seminar' => 'Seminar',
                        'conference' => 'Conference',
                        'meeting' => 'Meeting',
                    ]),
                    
                Tables\Filters\Filter::make('this_month')
                    ->query(fn (Builder $query): Builder => $query->whereMonth('start_date', now()->month))
                    ->label('This Month'),
            ])
            ->actions([
                // Tables\Actions\Action::make('view')
                //     ->icon('heroicon-m-eye')
                //     ->url(fn (Activity $record): string => route('filament.admin.resources.activities.view', $record))
                //     ->openUrlInNewTab(),
                    
                // Tables\Actions\Action::make('edit')
                //     ->icon('heroicon-m-pencil-square')
                //     ->url(fn (Activity $record): string => route('filament.admin.resources.activities.edit', $record))
                //     ->visible(fn (): bool => auth()->user()->can('update', Activity::class)),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make()
                //         ->visible(fn (): bool => auth()->user()->can('deleteAny', Activity::class)),
                // ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(8);
    }
    
    // Method untuk customize heading secara dinamis
    protected function getTableHeading(): string
    {
        $count = Activity::count();
        return "Kegiatan Terbaru";
    }
    
    // Method untuk menambah action di header widget
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('create')
                ->label('New Activity')
                ->icon('heroicon-m-plus')
                ->url(route('filament.admin.resources.activities.create'))
                ->visible(fn (): bool => auth()->user()->can('create', Activity::class)),
        ];
    }
    
    // Method untuk polling data (refresh otomatis)
    public function isTablePaginationEnabled(): bool
    {
        return true;
    }
}