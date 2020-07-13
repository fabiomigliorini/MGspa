<template>
  <mg-layout back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      Emissão de Etiquetas
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content" class="q-pa-sm space-end">
      <div class="row">

        <!-- Por codigo -->
        <div class="col-xs-12 col-sm-4 col-md-3 col-lg-2 col-xl-1 q-pa-sm">
          <q-form
            @submit="adicionarBarras"
            @reset="limparFormularioBarras"
            dense
            >
            <q-card>
              <q-card-section>
               <div class="text-h6">Por Código</div>
               <div class="text-subtitle2">Informe os códigos de cada produto</div>
              </q-card-section>

              <q-card-section class="q-pt-none">
                 <q-input
                   outlined
                   input-class="text-right"
                   type="number"
                   step="1"
                   min="1"
                   max="1000"
                   v-model="quantidadeetiqueta"
                   label="Quantidade"
                   error-message="Informe entre 1 e 1000!"
                   :error="quantidadeetiqueta<1 || quantidadeetiqueta>1000"
                />
                 <q-input
                   outlined
                   v-model="barras"
                   ref="barras"
                   label="Código de Barras"
                   @keyup="converteBarrasQuantidade"
                   error-message="Código de Barras não localizado"
                   :error="!isBarrasValido"
                   autofocus
                   >
                 </q-input>
              </q-card-section>
              <q-card-actions align="right">
               <q-btn label="Limpar" type="reset" color="primary" flat />
               <q-btn label="Adicionar" type="submit" color="primary" flat/>
              </q-card-actions>
           </q-card>
         </q-form>
       </div>

       <!-- por negocio -->
       <div class="col-xs-12 col-sm-4 col-md-3 col-lg-2 col-xl-1 q-pa-sm">
         <q-form
           @submit="adicionarNegocio"
           @reset="limparFormularioNegocio"
           dense
           >
           <q-card>
             <q-card-section>
              <div class="text-h6">Por Negócio</div>
              <div class="text-subtitle2">Adicione automaticamente todos produtos do negócio</div>
             </q-card-section>

             <q-card-section class="q-pt-none">
              <q-input
                outlined
                type="number"
                step="1"
                min="0"
                max="99999999"
                v-model="codnegocio"
                ref="codnegocio"
                label="#Negócio"
                error-message="Negócio não localizado!"
                :error="!isNegocioValido"
                >
              </q-input>
             </q-card-section>
             <q-card-actions align="right">
              <q-btn label="Limpar" type="reset" color="primary" flat />
              <q-btn label="Adicionar" type="submit" color="primary" flat/>
             </q-card-actions>
          </q-card>
        </q-form>
      </div>

       <!-- por periodo -->
       <div class="col-xs-12 col-sm-4 col-md-3 col-lg-2 col-xl-1 q-pa-sm">
         <q-form
           @submit="adicionarPeriodo"
           dense
           >
           <q-card>
             <q-card-section>
              <div class="text-h6">Por Período</div>
              <div class="text-subtitle2">Adicione automaticamente todos produtos com preços alterados no período</div>
             </q-card-section>

             <q-card-section class="q-pt-none">
               <q-input
                 outlined
                 input-class="text-center"
                 type="date"
                 :max="dataFinal"
                 v-model="dataInicial"
                 ref="dataInicial"
                 label="Data Inicial"
                 stack-label
                 error-message="Data Invalida"
                 :error="dataFinal < dataInicial"
                 >
               </q-input>
               <q-input
                 outlined
                 input-class="text-center"
                 type="date"
                 :min="dataInicial"
                 :max="moment().startOf('day').format('YYYY-MM-DD')"
                 v-model="dataFinal"
                 ref="dataFinal"
                 label="Data Final"
                 stack-label
                 error-message="Data Invalida"
                 :error="dataFinal < dataInicial"
                 >
               </q-input>
             </q-card-section>
             <q-card-actions align="right">
              <!-- <q-btn label="Limpar" type="reset" color="primary" flat /> -->
              <q-btn label="Adicionar" type="submit" color="primary" flat/>
             </q-card-actions>
          </q-card>
        </q-form>
      </div>

     </div>
      <div class="row">
        <div class="col-xs-12 col-sm-4 col-md-3 col-lg-2 col-xl-1 q-pa-sm" v-for="(etiqueta, index) in etiquetas">
          <q-card>
            <img :src="etiqueta.imagem" v-if="etiqueta.imagem">
            <img src="/statics/no-image-4-4.svg" v-else>

            <q-list>
              <q-item clickable @click="abrirProduto(etiqueta.codproduto)">
                <q-item-section>
                  <q-item-label class="ellipsis">{{etiqueta.produto}}</q-item-label>
                  <q-item-label caption>#{{numeral(parseFloat(etiqueta.codproduto)).format('000000')}}</q-item-label>
                </q-item-section>
              </q-item>

              <q-item>
                <q-item-section>
                  <q-item-label>{{etiqueta.barras}}</q-item-label>
                  <q-item-label caption>{{numeral(parseFloat(etiqueta.quantidadeetiqueta)).format('0,0')}} Etiqueta(s)</q-item-label>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{numeral(parseFloat(etiqueta.preco)).format('0,0.00')}}</q-item-label>
                  <q-item-label caption>
                    {{etiqueta.unidademedidasigla}}
                    <template v-if="etiqueta.quantidadeembalagem > 1">
                      C/{{etiqueta.quantidadeembalagem}}
                    </template>
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            <q-card-actions align="right">
              <q-btn icon="delete" color="primary" @click="removerEtiqueta(index)" flat>Remover</q-btn>
            </q-card-actions>
          </q-card>

        </div>
      </div>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="print" color="primary"  @click="prompt = true" />
      </q-page-sticky>
      <q-dialog v-model="prompt" persistent>
        <q-card style="min-width: 350px">
          <q-form
          @submit="imprimir"
          dense
          >
            <q-card-section>
              <div class="text-h6">Imprimir</div>
            </q-card-section>

            <q-card-section class="q-pt-none">
              <q-select
                v-model="modelo"
                :options="modelos"
                label="Modelo"
                outlined
                error-message="Informe o modelo da Etiqueta!"
                :error="!modelo"
                />
              <q-select
                v-model="impressora"
                :options="impressoras"
                label="Impressora"
                outlined
                error-message="Informe aa Impressora!"
                :error="!impressora"
                />
              <!-- <q-input dense v-model="address" autofocus @keyup.enter="prompt = false" /> -->
            </q-card-section>

            <q-card-actions align="right" class="text-primary">
              <q-btn flat label="Cancelar" v-close-popup />
              <q-btn flat label="Imprimir" type="submit"/>
            </q-card-actions>
          </q-form>
        </q-card>
      </q-dialog>
    </div>

  </mg-layout>
</template>

<script>
import { debounce } from 'quasar'
// import { throttle } from 'quasar'
// import { Notify } from 'quasar'
import MgLayout from '../../../layouts/MgLayout'
export default {
  components: {
    MgLayout,
    debounce,
  },
  data () {
    return {
      prompt: false,
      barras: null,
      impressora: null,
      impressoras: [],
      modelo: null,
      modelos: [
        {
          value: '3colunas',
          label: 'Pequena com 3 Colunas por linha'
        },
        {
          value: '3colunas_sempreco',
          label: 'Pequena com 3 Colunas por linha - Sem Preço'
        },
        {
          value: '2colunas',
          label: 'Média com 2 Colunas por linha'
        },
        {
          value: '2colunas_sempreco',
          label: 'Média com 2 Colunas por linha - Sem Preço'
        },
        {
          value: 'gondola',
          label: 'Grande para Gondola'
        },
      ],
      isBarrasValido: true,
      isNegocioValido: true,
      quantidadeetiqueta: 1,
      etiquetas: [],
      codnegocio: null,
      dataInicial: this.moment().startOf('day').subtract(1, 'days').format('YYYY-MM-DD'),
      dataFinal: this.moment().startOf('day').subtract(1, 'days').format('YYYY-MM-DD'),
    }
  },
  watch: {
  },

  computed: {
  },

  methods: {

    limparFormularioBarras: debounce(function () {
      this.quantidadeetiqueta = 1;
      this.barras = null;
    }),

    limparFormularioNegocio: debounce(function () {
      this.codnegocio = null;
    }),

    adicionarBarras: debounce(function () {
      // inicializa variaveis
      let vm = this;
      let params = {
        barras: this.barras
      }
      let quantidadeetiqueta = this.quantidadeetiqueta;
      this.limparFormularioBarras();
      // faz chamada api
      vm.$axios.get('etiqueta/barras', {params: params}).then(response => {
        this.isBarrasValido = true;
        response.data.data.quantidadeetiqueta = quantidadeetiqueta;
        this.etiquetas.unshift(response.data.data);
        this.$q.notify({
          color: 'positive',
          message: quantidadeetiqueta + ' etiqueta(s) do "' + response.data.data.produto + '" adicionada(s)!'
        });
      }).catch(error => {
        this.isBarrasValido = false;
      });
      this.$refs.barras.focus();
    }),

    adicionarNegocio: debounce(function () {
      // inicializa variaveis
      let vm = this;
      let params = {
        codnegocio: parseInt(this.codnegocio)
      }
      this.limparFormularioNegocio();
      // faz chamada api
      vm.$axios.get('etiqueta/negocio', {params: params}).then(response => {
        this.isNegocioValido = true;
        this.etiquetas = [].concat(response.data.data, this.etiquetas);
        this.$q.notify({
          color: 'positive',
          message: response.data.data.length + ' etiqueta(s) adicionada(s)!'
        });
      }).catch(error => {
        this.isNegocioValido = false;
      });
      this.$refs.codnegocio.focus();
    }),

    adicionarPeriodo: debounce(function () {
      // inicializa variaveis
      let vm = this;
      let params = {
        datainicial: this.dataInicial,
        datafinal: this.dataFinal
      }
      // faz chamada api
      vm.$axios.get('etiqueta/periodo', {params: params}).then(response => {
        this.etiquetas = [].concat(response.data.data, this.etiquetas);
        this.$q.notify({
          color: 'positive',
          message: response.data.data.length + ' etiqueta(s) adicionada(s)!'
        });
      }).catch(error => {
        this.$q.notify({
          color: 'negative',
          message: 'Falha ao buscar produtos com preços alterados no período!'
        });
      });
      this.$refs.codnegocio.focus();
    }),

    removerEtiqueta: function(index) {
      this.etiquetas.splice(index, 1);
    },

    converteBarrasQuantidade: function(event) {
      this.isBarrasValido = true;
      if (event.key == "*") {
        var quantidadeetiqueta = parseInt(this.barras.replace(/\D/g,''));
        if (quantidadeetiqueta > 1) {
          this.quantidadeetiqueta = quantidadeetiqueta;
        } else {
          this.quantidadeetiqueta = 1;
        }
        this.barras = null;
      }
    },

    abrirProduto: function(codproduto) {
      var win = window.open(process.env.MGLARA_URL + '/produto/' + codproduto, '_blank');
    },

    carregarImpressoras: debounce(function () {
      let vm = this;
      vm.$axios.get('etiqueta/impressoras').then(response => {
        this.impressoras = response.data;
      })
    }, 500),

    imprimir: debounce(function () {
      console.log('imprimir');
      let vm = this;
      let params = {
        modelo: this.modelo.value,
        impressora: this.impressora,
        etiquetas: this.etiquetas
      };
      vm.$axios.post('etiqueta/imprimir', params).then(response => {
        this.$q.notify({
          color: 'positive',
          message: response.data.quantidadeetiqueta + ' etiqueta(s) impressas na ' + response.data.impressora
        });
        this.prompt = false;
        console.log(response.data);
      }).catch(error => {
        console.log(response.data);
        this.prompt = false;
      });

    }, 500),

  },
  created () {
    this.carregarImpressoras();
  }
}
</script>

<style>
</style>
