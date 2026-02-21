<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Tickets de Soporte';

    protected static ?string $navigationGroup = 'Soporte';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Ticket';

    protected static ?string $pluralModelLabel = 'Tickets de Soporte';

    public static function getNavigationBadge(): ?string
    {
        return (string) SupportTicket::whereIn('status', ['open', 'in_progress'])->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = SupportTicket::where('status', 'open')->count();
        return $count > 0 ? 'danger' : 'success';
    }

    public static function canCreate(): bool
    {
        return false; // Los tickets los crean los usuarios desde la app
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Ticket')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Usuario')
                            ->relationship('user', 'name')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('subject')
                            ->label('Asunto')
                            ->disabled()
                            ->columnSpan(1),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Gestión')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(SupportTicket::statuses())
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->label('Prioridad')
                            ->options(SupportTicket::priorities())
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->label('Categoría')
                            ->options(SupportTicket::categories())
                            ->disabled(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Asignado a')
                            ->options(User::where('role', 'admin')->pluck('name', 'id'))
                            ->nullable()
                            ->searchable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.role')
                    ->label('Rol')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'modelo' => 'success',
                        'cliente' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => SupportTicket::categories()[$state] ?? $state)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'critical' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => SupportTicket::priorities()[$state] ?? $state),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'open' => 'danger',
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => SupportTicket::statuses()[$state] ?? $state),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Mensajes')
                    ->counts('messages')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedAdmin.name')
                    ->label('Asignado')
                    ->default('Sin asignar')
                    ->color(fn($state) => $state === 'Sin asignar' ? 'gray' : 'primary'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options(SupportTicket::statuses()),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options(SupportTicket::priorities()),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(SupportTicket::categories()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Gestionar'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            SupportTicketResource\RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportTickets::route('/'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
        ];
    }
}
