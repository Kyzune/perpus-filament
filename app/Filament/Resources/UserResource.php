<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                  ->required()
                  ->maxLength(255),
                Forms\Components\TextInput::make('email')
                  ->email()
                  ->required()
                  ->maxLength(255),
                Forms\Components\TextInput::make('password')
                  ->password()
                  ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                  ->dehydrated(fn ($state) => filled($state))
                  ->required(fn (string $context): bool => $context === 'create'),
                Forms\Components\Select::make('role')
                  ->options([
                    'super_admin' => 'Super Admin',
                    'petugas_perpus' => 'Petugas Perpus',
                    'siswa' => 'Siswa'
                  ])
                  ->required(),
                Forms\Components\TextInput::make('balance')
                    ->numeric()
                    ->default(10000)
                    ->visible(fn (callable $get) => $get('role') === 'siswa'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\BadgeColumn::make('role')
                  ->colors([
                    'primary' => 'super_admin',
                    'success' => 'petugas_perpus',
                    'warning' => 'siswa',
                  ]),
                Tables\Columns\TextColumn::make('balance')
                  ->money('idr')
                  ->getStateUsing(function (User $record): ?string {
                    return $record->role === 'siswa' ? $record->balance : null;
                  })
                  ->placeholder('-'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
      return auth()->user()->role === 'super_admin';
    }
}
