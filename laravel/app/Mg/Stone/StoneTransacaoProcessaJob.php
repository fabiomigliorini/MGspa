<?php

namespace Mg\Stone;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class StoneTransacaoProcessaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $stone_transaction_id;
    protected $establishment_id;

    public function __construct($establishment_id, $stone_transaction_id)
    {
        $this->establishment_id = $establishment_id;
        $this->stone_transaction_id = $stone_transaction_id;
    }

    public function handle()
    {
        if (!$stoneFilial = StoneFilial::where('establishmentid', $this->establishment_id)->first()) {
            return;
        }
        StoneTransacaoService::consultaPeloStoneId($stoneFilial, $this->stone_transaction_id);
    }
}
