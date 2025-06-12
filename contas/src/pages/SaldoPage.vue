<template>
  <MGLayout>
    <template #tituloPagina> Saldo </template>
    <template #content>
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
                             style="text-decoration: none; color:dodgerblue" :class="moneyTextColor(port.saldobancario)"
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
            <q-td :props="props" :class="'text-weight-bold bg-yellow-1 ' + moneyTextColor(props.value)" style="vertical-align: top;">
              {{ formatMoney(props.value) }}
            </q-td>
          </template>

          <!-- Rodapé -->
          <template v-slot:bottom-row>
            <q-tr class="bg-yellow-1 text-weight-bold">

              <q-td key="banco">Total por Filial</q-td>

              <q-td v-for="f in filiais" :key="f.codfilial" align="right" :class="moneyTextColor(f.totalFilial)">
                {{ formatMoney(f.totalFilial) }}
              </q-td>

              <q-td align="right" :class="moneyTextColor(totalGeral)">
                {{ formatMoney(totalGeral) }}
              </q-td>
            </q-tr>
          </template>

          <template v-slot:loading>
            <q-inner-loading showing color="primary" />
          </template>
        </q-table>

        <q-date v-model="dataSelecionada" minimal flat bordered v-if="intervalo"
                mask="DD-MM-YYYY"
                :navigation-min-year-month="$moment(intervalo.primeira_data).format('YYYY/MM')"
                :navigation-max-year-month="$moment(intervalo.ultima_data).format('YYYY/MM')"/>
      </div>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab :loading="importandoOfx" class="" color="primary" icon="upload_file"
               @click="openDialog">
          <template v-slot:loading>
            <q-spinner-oval  />
          </template>
        </q-btn>
      </q-page-sticky>

      <q-dialog v-model="ofxDialog" persistent>
        <q-card style="min-width: 800px">
          <q-card-section>
            <q-uploader
                ref="uploader"
                :url="urlUploadOfx"
                field-name="arquivos[]"
                accept=".ofx"
                label="Importar Arquivos OFX"
                @finish="finalImportacaoOfx"
                @uploaded="ofxImportado"
                @failed="ofxFalha"
                @uploading="enviandoOfx = true"
                :headers="[{name:'Authorization', 'value':`Bearer ${authStore().token}`}]"
                multiple
                flat style="width: 100%">
<!--              <template v-slot:list="scope">
                <q-list separator>
                  <q-item v-for="file in scope.files" :key="file.__key">
                    <q-item-section>
                      <q-item-label class="full-width ellipsis">
                        {{ file.name }}
                      </q-item-label>

                      <q-item-label caption>
                        Status: {{ file.__status }}
                        <span v-if="file.error" class="text-negative"> - Erro: {{ file.error }}</span>
                      </q-item-label>
                    </q-item-section>

                    <q-item-section top side>
                      <q-btn
                        class="gt-xs"
                        size="12px"
                        flat
                        dense
                        round
                        icon="delete"
                        @click="scope.removeFile(file)"
                      />
                    </q-item-section>
                  </q-item>
                </q-list>
              </template>-->
            </q-uploader>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat label="Fechar" color="primary" v-close-popup :disable="enviandoOfx" />
          </q-card-actions>
        </q-card>
      </q-dialog>
    </template>
  </MGLayout>
</template>

<script>
import MGLayout from 'layouts/MGLayout.vue'
import { formatMoney } from 'src/utils/formatters.js'
import { authStore } from 'stores/auth'

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
      dataSelecionada: null,
      ofxDialog: false,
      falhaImportacaoOfx: false,
      enviandoOfx:false,
      intervalo: null,
    }
  },
  methods: {
    authStore,
    buscaIntervaloSaldos(){
      //this.buscandoIntervalo = true;
      this.$api
        .get(`v1/portador/intervalo-saldos`, {})
        .then((response) => {
          const intervalo = response.data;
          console.log("intervalo", intervalo);
          //this.criaListaIntervalo(intervalo);
          this.intervalo = intervalo;
        })
        .catch((error) => {
          console.error('Erro:', error)
        })
        .finally(() => {
          //this.buscandoIntervalo = false;
          //resolve()
        })
    },
    openDialog() {
      this.falhaImportacaoOfx = false;
      this.ofxDialog = true;
    },
    finalImportacaoOfx: function() {
      this.enviandoOfx = false;
      if (!this.falhaImportacaoOfx) {
        this.ofxDialog = false;
      }
      this.buscaIntervaloSaldos();
      this.listaSaldos();
    },

    ofxImportado: function(info) {
      const vm = this;
      const resp = JSON.parse(info.xhr.response);
      Object.keys(resp).forEach(arquivo => {
        var mensagem =
          'Importados ' +
          resp[arquivo].registros +
          ' registros no portador "' +
          resp[arquivo].portador +
          '" com ' +
          resp[arquivo].falhas +
          ' falhas!'
        ;
        vm.$q.notify({
          message: mensagem,
          color: 'positive',
        })
      });
    },

    ofxFalha: function(response) {
      this.falhaImportacaoOfx = true;
      const vm = this;
      response.files.forEach((arquivo, i) => {
        var mensagem =
          'Falha ao importar o arquivo "' +
          arquivo.name +
          '"!'
        ;
        vm.$q.notify({
          message: mensagem,
          color: 'negative',
        })
      });
    },
    formatMoney,
    moneyTextColor(value){
      let classes = ""
      if(value >= 0){
        //classes += ' text-blue'
      }else{
        classes += ' text-red'
      }
      return classes
    },
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
          name: String(idx), label: filial.nome ? filial.nome : 'Sem Filial', field: String(idx), align: 'right'
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
  },
  computed: {
    urlUploadOfx: function () {
      return process.env.API_URL + 'v1/portador/importar-ofx';
    },
  },
  watch: {
    dataSelecionada(data){
      this.$router.push({
        query: {
          dia: data,
        }
      })
      this.listaSaldos();
    }
  },
  mounted() {
    this.dataSelecionada = this.$route.query.dia
    if(!this.dataSelecionada){
      this.dataSelecionada = this.$moment().format('DD-MM-YYYY')
    }
    this.buscaIntervaloSaldos();
  },
}
</script>

<style scoped>

</style>
