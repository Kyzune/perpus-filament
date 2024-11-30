<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BorrowingResource\Pages;
use App\Filament\Resources\BorrowingResource\RelationManagers;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;

class BorrowingResource extends Resource
{
    protected static ?string $model = Borrowing::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Student')
                    ->options(User::where('role', 'siswa')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('book_id')
                    ->label('Book')
                    ->options(Book::where('stock', '>', 0)->pluck('title', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('borrowed_at')
                    ->required(),
                Forms\Components\DatePicker::make('returned_at'),
                Forms\Components\Select::make('status')
                    ->options([
                        'borrowed' => 'Borrowed',
                        'returned' => 'Returned'
                    ])
                    -> required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Student')
                    ->searchable(),
                Tables\Columns\TextColumn::make('book.title')
                    ->label('Book')
                    ->searchable(),
                Tables\Columns\TextColumn::make('borrowed_at')->date(),
                Tables\Columns\TextColumn::make('returned_at')->date(),
                Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'warning' => 'borrowed',
                    'success' => 'returned'
                ])

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'borrowed' => 'Borrowed',
                        'returned' => 'Returned'
                    ]),
                Tables\Filters\Filter::make('borrowed_at')
                    ->form([
                        Forms\Components\DatePicker::make('borrowed_from'),
                        Forms\Components\DatePicker::make('borrowed_until')
                    ])
                    ->query(function(Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['borrowed_from'],
                                fn (Builder $query, $date) : Builder => $query->whereDate('borrowed_at', '>=', $date),
                            )
                            ->when(
                                $data['borrowed_until'],
                                fn (Builder $query, $date) : Builder => $query-> whereDate('borrowed_at', '<=', $date)
                            );
                    }),
                Tables\Filters\Filter::make('returned_at')
                    ->form([
                        Forms\Components\DatePicker::make('returned_from'),
                        Forms\Components\DatePicker::make('returned_until')
                    ])
                    ->query(function(Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['returned_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('returned_at', '>=', $date),
                            )
                            ->when(
                                $data['returned_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('returned_at', '<=', $date)
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('return')
                    ->label('Return Book')
                    ->icon('heroicon-o-check')
                    ->action(function (Borrowing $record) {
                        $record->update([
                            'status' => 'returned',
                            'returned_at' => now(),
                        ]);

                        // Beberapa proses debugging 
                        $user = $record->user;
                        Log::info("User Balance before Fine: {$user->balance}");
                        $dueDate = $record->borrowed_at->addDays(14);
                        Log::info("Dipinjam tanggal: {$record->borrowed_at}");
                        Log::info("Batas Pengembalian: {$dueDate}");
                        Log::info("Dikembalikan tanggal: {$record->returned_at}");
                        $daysLate = Carbon::now()->diffInDays($dueDate, true);
                        Log::info("Days Late: $daysLate");
                        if ($daysLate > 0) {
                            $fine = $daysLate * 1000;
                            Log::info("Denda terhitung: $fine");
                            $user->decrement('balance', min($fine, $user->balance));
                            Log::info("User Balance after Fine: {$user->balance}");
                        }


                    })
                    -> visible(function(Borrowing $record) {
                        $user = auth()->user();
                        return $record->status === 'borrowed' && in_array($user->role, ['super_admin', 'petugas_perpus']);
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        -> visible(function () {
                            $user = auth()->user();
                            return in_array($user->role, ['super_admin', 'petugas_perpus']);
                        })
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBorrowings::route('/'),
            'create' => Pages\CreateBorrowing::route('/create'),
            'edit' => Pages\EditBorrowing::route('/{record}/edit'),
        ];
    
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();
        return in_array($user->role, ['super_admin', 'admin']);
        
    }
    public static function canCreate(): bool
    {
        $user = auth()->user();
        return in_array($user->role, ['super_admin', 'admin']);
    }
}