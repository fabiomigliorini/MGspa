<template>
  <mg-layout drawer back-path="/">
    <template slot="title">
      Nova Requisição de Trasnferência
    </template>

    <template slot="tabHeader">
      <q-checkbox color="secondary" v-model="filtro.selecionartodos"></q-checkbox>
    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer">
      <q-list>

        <!-- Origem -->
        <q-item dense>
          <q-item-section>
            <mg-select-estoque-local label="Origem" v-model="filtro.codestoquelocalorigem"/>
          </q-item-section>
        </q-item>

        <!-- Destino -->
        <q-item dense>
          <q-item-section>
            <mg-select-estoque-local label="Destino" v-model="filtro.codestoquelocaldestino"/>
          </q-item-section>
        </q-item>

      </q-list>
    </template>

    <div slot="content">

      <q-tabs v-model="tabs" inline-label class="bg-primary text-white shadow-2">
        <q-tab name="produtos" label="Produtos" default></q-tab>
        <q-tab name="faltando" label="Faltando" default></q-tab>
        <q-tab name="separar" label="Separar"></q-tab>
        <q-tab name="conferir" label="Separado"></q-tab>
        <q-tab name="transito" label="Conferido"></q-tab>
        <q-tab name="entregue" label="Entregue"></q-tab>
      </q-tabs>

      <q-list separator>

        <q-item v-for="produto in produtos" :key="produto.codprodutovariacao">
          <!-- <q-item-side avatar="statics/boy-avatar.png" /> -->
          <q-item-section>
            <q-checkbox v-model="produto.selecionado"></q-checkbox>
          </q-item-section>

          <q-item-section>

            <q-tooltip>
              <q-chip detail square dense icon="vpn_key">
                #{{ numeral(produto.codproduto).format('000000') }}
              </q-chip>
              <br />

              <q-chip detail square dense icon="local_offer">
                {{ produto.referencia }}
              </q-chip>
              <br />

              <template v-for="barras in produto.barras">
                <q-chip detail square dense icon="view_column">
                  {{ barras.barras }}
                  <template v-if="barras.quantidade">
                    {{ barras.sigla }}
                    C/{{ numeral(parseFloat(barras.quantidade)).format('0,0') }}
                  </template>
                </q-chip>
                <br />
              </template>

            </q-tooltip>
            <q-item-label>
              {{ produto.produto }}
              <a :href="'/estoque-estatistica/' + produto.codproduto + '?codprodutovariacao='+produto.codprodutovariacao" target="_blank" tabindex="-1">
                <q-icon name="multiline_chart" />
              </a>
            </q-item-label>

            <q-item-label caption>
              {{ produto.variacao }}
            </q-item-label>
          </q-item-section>

          <q-item-section class="col-xs-2 col-md-2 col-lg-1">
            <q-input v-model="produto.transferir" type="number"  min="0" :step="produto.lotetransferencia" :max="produto.saldoquantidade_origem"/>
          </q-item-section>

          <q-item-section>
            <q-tooltip>
              Saldo de {{ numeral(parseFloat(produto.saldoquantidade)).format('0,0') }}
              {{ produto.um }}. <br />
              Sugerido entre
              {{ numeral(parseFloat(produto.estoqueminimo)).format('0,0') }}
              e
              {{ numeral(parseFloat(produto.estoquemaximo)).format('0,0') }}
              {{ produto.um }}.
              Disponível na origem:
              {{ numeral(parseFloat(produto.saldoquantidade_origem)).format('0,0') }}
              {{ produto.um }}.
            </q-tooltip>
            <q-item-label caption>
              {{ numeral(parseFloat(produto.saldoquantidade)).format('0,0') }}
              {{ produto.um }}
            </q-item-label>
            <!-- {{ numeral(parseFloat(produto.preco)).format('0,0.00') }} -->

            <!-- {{ numeral(parseFloat(produto.saldopercentual)).format('0,0') }} -->

            <q-item-label>
              <q-icon :name="bateria(produto.saldopercentual)" :color="corBateria(produto.saldopercentual)" />
            </q-item-label>
            <!-- {{ numeral((parseFloat(produto.saldoquantidade) / parseFloat(produto.estoquemaximo))*100).format('0,0.00') }} -->
          </q-item-section>
        </q-item>

      </q-list>
      <br />
      <br />
      <br />
      <q-page-sticky position="bottom-right" :offset="[18, 18]">
        <q-btn round color="primary" @click="confirmarRequisicoes()" icon="done" />
      </q-page-sticky>
    </div>
  </mg-layout>
</template>

<script>

import MgSelectEstoqueLocal from '../../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../../layouts/MgLayout'

export default {
  name: 'estoque-saldo-conferencia-index',
  components: {
    MgLayout,
    MgSelectEstoqueLocal
  },
  data () {
    return {
      tabs: 'produtos',
      produtos: [],
      filtro: {
        codestoquelocalorigem: 101001,
        codestoquelocaldestino: 102001
      }
    }
  },

  watch: {
    // observa filtro, sempre que alterado chama a api
    filtro: {
      handler: function (val, oldVal) {
        this.carregaListagem()
      },
      deep: true
    }
  },

  methods: {

    confirmarRequisicoes: function () {
      this.$q.dialog({
        title: 'Confirma Requisição',
        message: 'Será criada uma nova requisição de mercadorias.',
        ok: 'OK',
        cancel: 'Cancelar'
      }).then(() => {
        this.criarRequisicoes()
      }).catch(() => {
      })
    },

    criarRequisicoes: function () {

      let requisicoes = [];
      let vm = this;

      // monta array com requisicoes
      this.produtos.forEach(function (prod) {
        if (!(prod.transferir > 0) || !prod.selecionado) {
          return
        }
        requisicoes.push({
          codestoquelocalorigem: vm.filtro.codestoquelocalorigem,
          codestoquelocaldestino: vm.filtro.codestoquelocaldestino,
          codprodutovariacao: prod.codprodutovariacao,
          quantidade: prod.transferir
        })

      });

      // se nenhum produto retorna erro
      if (requisicoes.length == 0) {
        this.$q.notify({
          color: 'negative',
          message: 'Nenhum produto selecionado!'
        });
        return
      }

      vm.$axios.post('transferencia/requisicao', { requisicoes }).then(function(request){
        vm.$q.notify({
          message: 'Requisição criada!',
          color: 'positive',
        });
        vm.carregaListagem()
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Erro ao salvar!',
          color: 'negative',
        });
        console.log(error)
      });

      console.log(requisicoes.length)
    },

    bateria: function (percentual) {
      if (percentual >= 90) {
        return 'fas fa-battery-full'
      }
      if (percentual >= 70) {
        return 'fas fa-battery-three-quarters'
      }
      if (percentual >= 45) {
        return 'fas fa-battery-half'
      }
      if (percentual >= 20) {
        return 'fas fa-battery-quarter'
      }
      return 'fas fa-battery-empty'
    },

    corBateria: function (percentual) {
      if (percentual >= 60) {
        return 'primary'
      }
      if (percentual >= 30) {
        return 'warning'
      }
      return 'negative'
    },

    carregaListagem: function () {
      this.produtos = [];
      let params = {
        codestoquelocalorigem: this.filtro.codestoquelocalorigem,
        codestoquelocaldestino: this.filtro.codestoquelocaldestino
      };
      if (params.codestoquelocalorigem == null || params.codestoquelocaldestino == null) {
        return
      }
      if (params.codestoquelocalorigem == params.codestoquelocaldestino) {
        return
      }
      let vm = this;
      vm.$axios.get('transferencia/produtos-faltando-sem-requisicao', { params }).then(function(request){
        request.data.forEach(function (prod) {
          prod.selecionado = false
        });
        vm.produtos = request.data
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Erro ao carregar dados!',
          color: 'negative',
        });
        console.log(error)
      })
    }
  },
  created () {
    this.carregaListagem()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
