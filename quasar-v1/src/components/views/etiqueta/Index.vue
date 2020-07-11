<template>
  <mg-layout back-path="/">

    <!-- Título da Página -->
    <template slot="title">
      Emissão de Etiquetas
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content" class="q-pa-sm space-end">
      <div class="row q-gutter-sm">
        <div class="col-md-3 q-pa-sm">
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
                   v-model="quantidade"
                   label="Quantidade"
                   error-message="Informe entre 1 e 1000!"
                   :error="quantidade<1 || quantidade>1000"
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

      </div>
      <div class="row">
        <div class="col-md-3 q-pa-sm" v-for="(etiqueta, index) in etiquetas">
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
                  <q-item-label caption>{{numeral(parseFloat(etiqueta.quantidade)).format('0,0')}} Etiqueta(s)</q-item-label>
                </q-item-section>
                <q-item-section>
                  <q-item-label>{{numeral(parseFloat(etiqueta.preco)).format('0,0.00')}}</q-item-label>
                  <q-item-label caption>{{etiqueta.unidademedidasigla}}</q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
            <q-card-actions align="right">
              <q-btn icon="delete" color="negative" @click="removerEtiqueta(index)" flat>Remover</q-btn>
            </q-card-actions>
          </q-card>

        </div>
        {{etiquetas}}
      </div>

      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn fab icon="print" color="primary"  @click="prompt = true" />
      </q-page-sticky>
      <q-dialog v-model="prompt" persistent>
        <q-card style="min-width: 350px">
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
            <q-btn flat label="Imprimir" v-close-popup />
          </q-card-actions>
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
      impressoras: [
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
      quantidade: 1,
      // etiquetas: [ { "codproduto": 2, "codprodutobarra": 11, "barras": "7891027132798", "produto": "Caderno Tilibra 10x1 200fls Cd Corinthians", "preco": 1231223.511, "unidademedidasigla": "UN", "quantidade": 1 }, { "codproduto": 3, "codprodutobarra": 17, "barras": "7891027128296", "produto": "Caderno Tilibra 01x1 96fls Cd Jolie", "preco": 18.5, "unidademedidasigla": "UN", "quantidade": 1 } ],
      etiquetas: [],
      etiquetas: [ { "codproduto": 1, "codprodutobarra": 5, "barras": "7891027129675", "produto": "Caderno Tilibra 1/4 Brochura 48fls Cd Jolie", "preco": 5.5, "unidademedidasigla": "UN", "imagem": "http://localhost:91/imagens/10986.jpg", "quantidade": 1 }, { "codproduto": 2, "codprodutobarra": 11, "barras": "7891027132798", "produto": "Caderno Tilibra 10x1 200fls Cd Corinthians", "preco": 23.5, "unidademedidasigla": "UN", "imagem": "http://localhost:91/imagens/2599.jpg", "quantidade": 1 }, { "codproduto": 3, "codprodutobarra": 17, "barras": "7891027128296", "produto": "Caderno Tilibra 01x1 96fls Cd Jolie", "preco": 18.5, "unidademedidasigla": "UN", "imagem": "http://localhost:91/imagens/11412.jpg", "quantidade": 1 }, { "codproduto": 4, "codprodutobarra": 23, "barras": "7891027138981", "produto": "Caderno Tilibra 01x1 96fls Cd Princesas", "preco": 18.25, "unidademedidasigla": "UN", "imagem": "http://localhost:91/imagens/11454.jpg", "quantidade": 1 }, { "codproduto": 6, "codprodutobarra": 33, "barras": "78910271198", "produto": "Cartao Ponto Tilibra Impressos", "preco": 6.85, "unidademedidasigla": "PT", "imagem": null, "quantidade": 1 }, { "codproduto": 7, "codprodutobarra": 39, "barras": "7891027151355", "produto": "Nota Promissoria Tilibra Impressos 50fls Grande ", "preco": 1.5, "unidademedidasigla": "UN", "imagem": "http://localhost:91/imagens/75.jpg", "quantidade": 1 } ],
    }
  },
  watch: {
  },

  computed: {
  },

  methods: {

    limparFormularioBarras: debounce(function () {
      this.quantidade = 1;
      this.barras = null;
    }),

    adicionarBarras: debounce(function () {
      // inicializa variaveis
      let vm = this;
      // faz chamada api
      vm.$axios.get('etiqueta/barras', {params: {barras: this.barras}}).then(response => {
        this.limparFormularioBarras();
        this.isBarrasValido = true;
        response.data.data.quantidade = this.quantidade;
        this.etiquetas.unshift(response.data.data);
      }).catch(error => {
        this.isBarrasValido = false;
      });
      this.$refs.barras.focus();
    }),

    removerEtiqueta: function(index) {
      this.etiquetas.splice(index, 1);
    },

    converteBarrasQuantidade: function(event) {
      this.isBarrasValido = true;
      if (event.key == "*") {
        var quantidade = parseInt(this.barras.replace(/\D/g,''));
        if (quantidade > 1) {
          this.quantidade = quantidade;
        } else {
          this.quantidade = 1;
        }
        this.barras = null;
      }
    },

    abrirProduto: function(codproduto) {
      var win = window.open(process.env.MGLARA_URL + '/produto/' + codproduto, '_blank');
    },

    loadRetornoPendente: debounce(function () {
      // inicializa variaveis
      let vm = this;
      // faz chamada api
      vm.$axios.get('etiqueta/barras', {barras: this.barras}).then(response => {
        conslog.log(response.data);
        // vm.retornoPendente = response.data;
        // vm.retornoPendente.forEach((portador) => {
        //   portador.retornos.forEach((arquivo) => {
        //     vm.retornoPendenteSelecionado = vm.retornoPendenteSelecionado.concat([portador.codportador + '-' + arquivo]);
        //   });
        // });
        // this.loadingRetornoPendente = false;
        // vm.decideTabRetorno();
      })
    }, 500),

  },
  created () {
    // this.loadRetornoPendente();
  }
}
</script>

<style>
</style>
