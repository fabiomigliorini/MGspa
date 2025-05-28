<template>
  <MGLayout>
    <template #tituloPagina> Saldo </template>
    <template #content>
      <div class="q-ma-md">
        <q-skeleton  height="90px" v-if="buscandoIntervalo"/>
        <div style="display: flex; flex-direction: column" v-else>
          <q-btn-toggle unelevated class="q-mb-sm"
                        v-model="anoSelecionado" :options="anos" :disable="isLoading"
                        color="white" text-color="primary" toggle-color="primary"  />

          <q-btn-toggle unelevated class="q-mb-sm"
                        v-model="mesSelecionado" :options="meses" :disable="isLoading"
                        color="white" text-color="primary" toggle-color="primary"  />
        </div>

      </div>

      <div class="q-mx-md" style="display: flex; justify-content: end;gap:8px">

        <q-btn :loading="importandoOfx" color="primary" icon="upload_file"
               @click="$refs.inputArquivo.click()" label="Importar OFX">
          <template v-slot:loading>
            <q-spinner-oval class="on-left" />
            Processando...
          </template>
        </q-btn>

        <input ref="inputArquivo" type="file" accept=".ofx" style="display: none"
               @change="uploadOfx" />
      </div>

      <div class="q-pa-md">
        <q-table :columns="columns" :rows="rows" row-key="banco"
          flat bordered hide-pagination
          no-data-label="Nenhum dado disponível"
          :loading="isLoading || buscandoIntervalo">

          <!-- Coluna Banco -->
          <template v-slot:body-cell-banco="props">
            <q-td :props="props" class="text-weight-bold" style="vertical-align: top;">
              {{ props.row.banco }}
            </q-td>
          </template>

          <!-- Colunas de Filial -->
          <template v-slot:body-cell="props">
            <q-td :props="props" class="q-pa-0" style="vertical-align: top;">
              <div v-if="Array.isArray(props.value) && props.value.length">
                <q-btn v-for="port in props.value" :key="port.codportador"
                  dense flat class="text-weight-light"
                  :to="{
                      name: 'extrato',
                      params: {
                        id: port.codportador,
                        ano: this.anoSelecionado, mes:
                        this.mesSelecionado + 1 },
                    }">
                  {{ formatMoney(port.saldo) }}
                </q-btn>
              </div>
              <div v-else class="text-grey">---</div>
            </q-td>
          </template>

          <!-- Coluna Total por Banco -->
          <template v-slot:body-cell-totalBanco="props">
            <q-td :props="props" class="text-weight-bold bg-yellow-1" style="vertical-align: top;">
              {{ formatMoney(props.value) }}
            </q-td>
          </template>

          <!-- Rodapé -->
          <template v-slot:bottom-row>
            <q-tr class="bg-yellow-1 text-weight-bold">

              <q-td key="banco">Total por Filial</q-td>

              <q-td v-for="f in filiais" :key="f.codfilial" align="right">
                {{ formatMoney(f.totalFilial) }}
              </q-td>

              <q-td align="right">
                {{ formatMoney(totalGeral) }}
              </q-td>
            </q-tr>
          </template>

          <template v-slot:loading>
            <q-inner-loading showing color="primary" />
          </template>
        </q-table>

      </div>
    </template>
  </MGLayout>
</template>

<script>
import MGLayout from 'layouts/MGLayout.vue'
import { formatMoney } from 'src/utils/formatters.js'
import { nextTick, watch } from 'vue'

export default {
  components: { MGLayout },
  data() {
    return {
      isLoading: false,
      filiais: [],
      totalPorBanco: 0,
      totalGeral: 0,
      columns: [],
      rows: [],
      footer: {},

      anoSelecionado : null,
      mesSelecionado : null,

      buscandoIntervalo: false,
      intervaloSaldos: {
        inicio: null,
        fim: null,
      },
      importandoOfx: false
    }
  },
  methods: {
    formatMoney,
    buscaIntervaloSaldos(){
      this.buscandoIntervalo = true;
      this.$api
        .get(`v1/portador/intervalo-saldos`, {})
        .then((response) => {
          const intervalo = response.data;

          this.intervaloSaldos = {
            inicio: intervalo.primeira_data,
            fim: intervalo.ultima_data,
          }
          this.anoSelecionado =  this.$moment().year();
        })
        .catch((error) => {
          console.error('Erro:', error)
        })
        .finally(() => {
          this.buscandoIntervalo = false;
        })
    },

    listaSaldos(){
      console.log("listaSaldos")
      if (this.isLoading) {
        return;
      }

      this.isLoading = true
      this.$api
        .get(`v1/portador/lista-saldos`, {
          params: {
            mes: this.mesSelecionado + 1,
            ano: this.anoSelecionado,
          },
        })
        .then((response) => {
          this.filiais = response.data.data.filiais
          this.totalPorBanco = response.data.data.totalPorBanco;
          this.totalGeral = response.data.data.totalGeral
          this.montaTabela()
        })
        .catch((error) => {
          console.error('Erro:', error)
        })
        .finally(() => {
          this.isLoading = false;
        })
    },

    montaTabela() {
      this.columns = [
        { name: 'banco', label: 'Banco', field: 'banco', align: 'left' },
        ...this.filiais.map((filial, idx) => ({
          name: String(idx), label: filial.nome, field: String(idx), align: 'right'
        })),
        { name: 'totalBanco',  label: 'Total Banco', field: 'totalBanco',  align: 'right' }
      ];

      const linhasPorBanco = {};

      this.filiais.forEach((filial, idxFilial) => {
        filial.bancos.forEach(banco => {
          const codbanco = banco.codbanco;

          if (!linhasPorBanco[codbanco]) {
            linhasPorBanco[codbanco] = {
              banco: banco.nome,
              totalBanco: 0
            };

            this.filiais.forEach((_, idx) => {
              linhasPorBanco[codbanco][String(idx)] = [];
            });
          }

          linhasPorBanco[codbanco][String(idxFilial)] = banco.portadores;

          const total = this.totalPorBanco.find(t => t.codbanco === codbanco);
          linhasPorBanco[codbanco].totalBanco = total ? total.valor : 0;
        });
      });

      this.rows = Object.values(linhasPorBanco);
    },
    uploadOfx(event){
      this.importandoOfx = true;
      const arquivos = event.target.files;

      const formData = new FormData();
      for (let i = 0; i < arquivos.length; i++) {
        formData.append('arquivos[]', arquivos[i]);
      }

      this.$api.post(`v1/portador/importar-ofx`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        .then((response) => {
          const resp = response.data;

          Object.keys(resp).forEach(arquivo => {
            var mensagem = `
              Importados ${resp[arquivo].registros}
              registros no portador ${resp[arquivo].portador}
              com ${resp[arquivo].falhas} falhas!'`;

            this.$q.notify({ message: mensagem, color: 'positive' })
          })

        })
        .catch((error) => {
          console.error('Erro:', error)
          /*info.files.forEach((arquivo, ) => {
            var mensagem =
              'Falha ao importar o arquivo "' +
              arquivo.name +
              '"!'
            ;
            vm.$q.notify({
              message: mensagem,
              color: 'negative',
              // position: 'top',
            })
          });*/
        })
        .finally(() => {
          this.importandoOfx = false;
          this.listaSaldos()
        })
    },
  },
  computed: {
    anos(){
      const anoInicio = this.$moment(this.intervaloSaldos.inicio).year()
      const anoFim   = this.$moment(this.intervaloSaldos.fim).year()
      const anos = [];
      for (let ano = anoInicio; ano <= anoFim; ano++) {
        anos.push(ano)
      }
      return anos.map(ano => {
        return {label:ano, value: ano}
      })
    },
    meses(){
      const inicio = this.$moment(this.intervaloSaldos.inicio)
      const fim   = this.$moment(this.intervaloSaldos.fim)

      const mesInicio = (this.anoSelecionado === inicio.year() ? inicio.month() : 0)
      const mesFim    = (this.anoSelecionado === fim.year()   ? fim.month()   : 11)

      const meses = []
      for (let mes = mesInicio; mes <= mesFim; mes++) {
        const dt = this.$moment({ year: this.anoSelecionado, month: mes })
        meses.push({
          value: mes,
          label: dt.format('MMM')
        })
      }
      return meses
    },
  },
  watch: {
    anoSelecionado(){
      this.mesSelecionado = this.meses[0].value
    }
  },
  mounted() {

    this.buscaIntervaloSaldos()

    watch(
      () => [
        this.anoSelecionado,
        this.mesSelecionado
      ],
      async () => {

        await nextTick()
        this.listaSaldos()
      }
    )
  },
}
</script>

<style scoped>

</style>
