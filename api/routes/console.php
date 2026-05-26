<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Schedule (porta do antigo app/Console/Kernel.php schedule())
|--------------------------------------------------------------------------
| Em L13 slim, schedule fica aqui. Cada item pode ser desativado
| comentando enquanto o command correspondente não estiver estável.
*/

Schedule::command('nota-fiscal:transferencia-entrada')->hourly();
Schedule::command('nfe-php:resolver-pendentes')->everyTenMinutes();
Schedule::command('nfe-php:dist-dfe')->everyThirtyMinutes();
Schedule::command('estoque:calcular-minimo-maximo --enviar-mail-faltando')->dailyAt('00:01');
Schedule::command('boleto-bb:consultar-liquidados')->twiceDaily(4, 13);
Schedule::command('pix:consultar --horas=36')->everyTenMinutes();
Schedule::command('aniversariantes:individual')->dailyAt('08:00');
Schedule::command('aniversariantes:geral')->dailyAt('08:00');
Schedule::command('ranking-produto:refresh')
    ->dailyAt('05:00')
    ->runInBackground()
    ->withoutOverlapping();
