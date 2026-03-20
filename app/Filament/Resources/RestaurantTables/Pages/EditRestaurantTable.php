<?php

namespace App\Filament\Resources\RestaurantTables\Pages;

use App\Filament\Resources\RestaurantTables\RestaurantTableResource;
use App\Models\RestaurantTable;
use App\Services\TableQrService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;

class EditRestaurantTable extends EditRecord
{
    protected static string $resource = RestaurantTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewQr')
                ->label('Ver / imprimir QR')
                ->icon(Heroicon::OutlinedQrCode)
                ->modalHeading('Código QR de la mesa')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar')
                ->modalContent(fn (): View => view('filament.modals.table-qr', [
                    'url' => $this->resolveQrPreviewUrl(),
                ])),
            Action::make('regenerateQr')
                ->label('Regenerar QR')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('¿Regenerar código QR?')
                ->modalDescription('Los códigos e impresiones anteriores dejarán de funcionar.')
                ->action(function (RestaurantTable $record): void {
                    $svc = app(TableQrService::class);
                    $secret = $svc->rotate($record);
                    $url = $svc->publicMenuUrl($record, $secret);
                    Notification::make()
                        ->title('QR regenerado')
                        ->body(new HtmlString(
                            '<p class="text-sm">Guardá el enlace en un lugar seguro (también podés abrirlo desde «Ver / imprimir QR» unos minutos):</p>'
                            .'<p class="mt-2 break-all text-xs font-mono">'.$url.'</p>'
                        ))
                        ->success()
                        ->persistent()
                        ->send();
                })
                ->visible(fn (): bool => auth()->user()?->can('tables.update') ?? false),
            DeleteAction::make(),
        ];
    }

    protected function resolveQrPreviewUrl(): string
    {
        $record = $this->getRecord();
        if (! $record instanceof RestaurantTable) {
            return '';
        }

        $svc = app(TableQrService::class);
        $secret = $svc->peekPlainSecretFromCache($record);

        return $secret ? $svc->publicMenuUrl($record, $secret) : '';
    }
}
