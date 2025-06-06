<template>
  <MGLayout>
    <template #tituloPagina> Saldo </template>
    <template #content>
      <div class="q-mx-md q-mt-md row justify-end" style="">
        <q-btn :loading="importandoOfx" class="" color="primary" icon="upload_file"
               @click="$refs.inputArquivo.click()" label="Importar OFX">
          <template v-slot:loading>
            <q-spinner-oval class="on-left" />
            Processando...
          </template>
        </q-btn>

        <input ref="inputArquivo" type="file" accept=".ofx" style="display: none"
               @change="uploadOfx" />
      </div>

      <div class="q-pa-md" style="display: flex; gap: 8px">
        <q-table :columns="columns" :rows="rows" row-key="banco"
          flat bordered hide-pagination style="flex-grow: 1"
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
              <div v-if="Array.isArray(props.value) && props.value.length" style="display: flex;flex-direction: column;">
                <router-link v-for="port in props.value" :key="port.codportador"
                             style="text-decoration: none; color:dodgerblue"
                             :to="{
                              name: 'extrato',
                              params: {
                                id: port.codportador,
                                mesAno: this.dataSelecionada.substring(3) },
                    }">{{ formatMoney(port.saldobancario) }}</router-link>
<!--                <q-btn v-for="port in props.value" :key="port.codportador"
                       dense flat class="text-weight-light"
                       :to="{
                      name: 'extrato',
                      params: {
                        id: port.codportador,
                        mesAno: this.dataSelecionada.substring(3) },
                    }">
                  {{ formatMoney(port.saldobancario) }}
                </q-btn>-->

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

        <q-date v-model="dataSelecionada" minimal flat bordered
                mask="DD-MM-YYYY"/>
      </div>
    </template>
  </MGLayout>
</template>

<script>
import MGLayout from 'layouts/MGLayout.vue'
import { formatMoney } from 'src/utils/formatters.js'

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

      buscandoIntervalo: false,
      intervaloSaldos: {
        inicio: null,
        fim: null,
      },
      importandoOfx: false,
      dataSelecionada: null
    }
  },
  methods: {
    formatMoney,
    listaSaldos(){
      if (this.isLoading) {
        return;
      }

      this.isLoading = true
      this.$api
        .get(`v1/portador/lista-saldos`, {
          params: {
            dia: this.dataSelecionada
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
      console.log("uploadOfx")
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
          var mensagem = error.response.data.message
          this.$q.notify({ message: mensagem, color: 'negative' })
        })
        .finally(() => {
          this.$refs.inputArquivo.value = null;
          this.importandoOfx = false;
          this.listaSaldos()
        })
    }
  },
  watch: {
    dataSelecionada(data){
      this.$router.push({
        query: {
          data: data,
        }
      })
      this.listaSaldos();
    }
  },
  mounted() {
    this.dataSelecionada = this.$route.query.data
    if(!this.dataSelecionada){
      this.dataSelecionada = this.$moment().format('DD-MM-YYYY')
    }
  },
}
</script>

<style scoped>

</style>
