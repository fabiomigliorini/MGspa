<?php

namespace Mg\Sequence;

use Illuminate\Support\Facades\DB;

class SequenceService
{
    public static function incrementa($sequence)
    {
        $seq = DB::select('select nextval(:sequence) as nextval', [$sequence]);
        return $seq[0]->nextval;
    }

    public static function ultimDaSessao($sequence)
    {
        $seq = DB::select('select currval(:sequence) as currval', [$sequence]);
        return $seq[0]->currval;
    }

    public static function ultimo($sequence)
    {
        $seq = DB::select("select last_value from {$sequence}");
        return $seq[0]->last_value;
    }

    public static function simulaProximo($sequence)
    {
        $seq = DB::select("select last_value, increment_by from {$sequence}");
        return $seq[0]->last_value + $seq[0]->increment_by;
    }
}
