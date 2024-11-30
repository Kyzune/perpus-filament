<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookResource\Pages;
use App\Filament\Resources\BookResource\RelationManagers;
use App\Models\Book;
use App\Models\Borrowing;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Carbon\Carbon;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('author')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('borrowed')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('author')->searchable(),
                Tables\Columns\TextColumn::make('stock'),
                Tables\Columns\TextColumn::make('borrowed'),
                Tables\Columns\TextColumn::make('borrowing_status')
                    ->label('Status')
                    ->visible(fn () => auth()->user()->role === 'siswa')
                    ->getStateUsing(function (Book $record) {
                        $existingBorrowing = Borrowing::where('user_id', auth()->id())
                            ->where('book_id', $record->id)
                            ->where('status', 'borrowed')
                            ->first();

                        return $existingBorrowing ? 'Sedang Dipinjam' : '';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Sedang Dipinjam' => 'warning',
                        default => 'success',
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('borrow')
                    ->label('Pinjam')
                    ->icon('heroicon-o-book-open')
                    ->visible(function (Book $record) {
                        if (auth()->user()->role !== 'siswa') {
                            return false;
                        }

                        $existingBorrowing = Borrowing::where('user_id', auth()->id())
                            ->where('book_id', $record->id)
                            ->where('status', 'borrowed')
                            ->first();

                        return !$existingBorrowing;
                    })
                    ->action(function (Book $record) {
                        if ($record->stock <= 0) {
                            return;
                        }

                        $existingBorrowing = Borrowing::where('user_id', auth()->id())
                            ->where('book_id', $record->id)
                            ->where('status', 'borrowed')
                            ->first();

                        if ($existingBorrowing) {
                            return;
                        }

                        Borrowing::create([
                            'user_id' => auth()->id(),
                            'book_id' => $record->id,
                            'borrowed_at' => Carbon::now(),
                            'status' => 'borrowed'
                        ]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Pinjam Buku')
                    ->modalDescription(fn (Book $record) => "Apakah anda yakin ingin meminjam buku '{$record->title}'?")
                    ->modalSubmitActionLabel('Ya, Pinjam')
                    ->successNotification(
                        notification: fn () => \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Buku berhasil dipinjam')
                            ->body('Buku telah berhasil dipinjam. Silahkan ambil buku di perpustakaan.')
                    )
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(function () {
                            $user = auth()->user();
                            return in_array($user->role, ['super_admin', 'petugas_perpus']);
                        }),
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
            'index' => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'edit' => Pages\EditBook::route('/{record}/edit'),
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