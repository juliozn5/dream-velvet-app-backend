<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    public function getTabs(): array
    {
        return [
            'abiertos' => Tab::make('Abiertos')
                ->icon('heroicon-o-exclamation-circle')
                ->badge(fn() => \App\Models\SupportTicket::where('status', 'open')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'open')),

            'en_progreso' => Tab::make('En Progreso')
                ->icon('heroicon-o-clock')
                ->badge(fn() => \App\Models\SupportTicket::where('status', 'in_progress')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'in_progress')),

            'resueltos' => Tab::make('Resueltos')
                ->icon('heroicon-o-check-circle')
                ->badge(fn() => \App\Models\SupportTicket::where('status', 'resolved')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'resolved')),

            'cerrados' => Tab::make('Cerrados')
                ->icon('heroicon-o-x-circle')
                ->badge(fn() => \App\Models\SupportTicket::where('status', 'closed')->count())
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'closed')),

            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-list-bullet')
                ->badge(fn() => \App\Models\SupportTicket::count()),
        ];
    }
}
