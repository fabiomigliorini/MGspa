<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToTblnotafiscalTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Adiciona coluna nullable temporariamente
        Schema::table('tblnotafiscal', function (Blueprint $table) {
            $table->string('status', 3)->nullable()->after('valortotal');
        });

        // Popular o status das notas existentes
        DB::statement("
            UPDATE tblnotafiscal SET status =
            CASE
                WHEN nfeinutilizacao IS NOT NULL THEN 'INU'
                WHEN nfecancelamento IS NOT NULL THEN 'CAN'
                WHEN nfeautorizacao IS NOT NULL THEN 'AUT'
                WHEN emitida = false THEN 'LAN'
                WHEN numero IS NULL THEN 'DIG'
                ELSE 'ERR'
            END
        ");

        // Torna o campo NOT NULL com default após popular
        DB::statement("
            ALTER TABLE tblnotafiscal
            ALTER COLUMN status SET DEFAULT 'DIG',
            ALTER COLUMN status SET NOT NULL
        ");

        // Adiciona CHECK constraint para validar os status possíveis
        DB::statement("
            ALTER TABLE tblnotafiscal
            ADD CONSTRAINT chk_notafiscal_status
            CHECK (status IN ('LAN', 'DIG', 'ERR', 'AUT', 'CAN', 'INU'))
        ");

        // Adiciona comentário explicando os status
        DB::statement("
            COMMENT ON COLUMN tblnotafiscal.status IS 'Status da nota fiscal:
            LAN = Lançada (emitida = false)
            DIG = Em Digitação (emitida = true e numero vazio)
            ERR = Não Autorizada (emitida = true, tem número, sem autorização)
            AUT = Autorizada (nfeautorizacao preenchido e não cancelada/inutilizada)
            CAN = Cancelada (nfecancelamento preenchido)
            INU = Inutilizada (nfeinutilizacao preenchido)'
        ");

        // Adiciona índice
        Schema::table('tblnotafiscal', function (Blueprint $table) {
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tblnotafiscal', function (Blueprint $table) {
            DB::statement("ALTER TABLE tblnotafiscal DROP CONSTRAINT IF EXISTS chk_notafiscal_status");
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
}
