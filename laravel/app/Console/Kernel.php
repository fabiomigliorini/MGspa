<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('nota-fiscal:transferencia-entrada')->hourly();
        $schedule->command('nfe-php:resolver-pendente')->everyTenMinutes();
        $schedule->command('nfe-php:dist-dfe')->hourly();
        $schedule->command('estoque:calcular-minimo-maximo --enviar-mail-faltando')->dailyAt('00:01');
        $schedule->command('boleto-bb:consultar-liquidados')->twiceDaily(4, 13);
        $schedule->command('pix:consultar --horas=36')->everyTenMinutes();
        $schedule->command('aniversariantes:individual')->dailyAt('08:00');
        $schedule->command('aniversariantes:geral')->dailyAt('08:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
