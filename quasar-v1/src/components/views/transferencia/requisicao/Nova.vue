<template>
  <mg-layout drawer back-path="/">
    <template slot="title">
      Nova Requisição de Trasnferência
    </template>

    <template slot="tabHeader">
      <!-- <q-tabs v-model="filter.conferidos"> -->
      <q-tabs>
        <q-tab slot="title" name="produtos" label="Produtos" default></q-tab>
        <q-tab slot="title" name="faltando" label="Faltando" default></q-tab>
        <q-tab slot="title" name="separar" label="Separar"></q-tab>
        <q-tab slot="title" name="conferir" label="Separado"></q-tab>
        <q-tab slot="title" name="transito" label="Conferido"></q-tab>
        <q-tab slot="title" name="entregue" label="Entregue"></q-tab>
      </q-tabs>

      <q-checkbox v-model="filtro.selecionartodos"></q-checkbox>

    </template>

    <!-- Menu Drawer (Esquerda) -->
    <template slot="drawer" width="200" style="width: 200px;">
      <q-list no-border>

        <!-- Origem -->
        <q-item tag="label">
          <q-item-main>
            <mg-select-estoque-local
            label="Origem"
            v-model="filtro.codestoquelocalorigem"
            required>
          </mg-select-estoque-local>
          </q-item-main>
        </q-item>

        <!-- Destino -->
        <q-item tag="label">
          <q-item-main>
            <mg-select-estoque-local
            label="Destino"
            v-model="filtro.codestoquelocaldestino"
            required>
          </mg-select-estoque-local>
          </q-item-main>
        </q-item>

      </q-list>
    </template>

    <div slot="content">


      <q-list highlight inset-separator>

        <q-item multiline v-for="produto in produtos" :key="produto.codprodutovariacao">
          <!-- <q-item-side avatar="statics/boy-avatar.png" /> -->
          <q-item-side>
            <q-checkbox v-model="produto.selecionado"></q-checkbox>
          </q-item-side>

          <q-item-main>

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
            <q-item-tile label lines="1">
              {{ produto.produto }}
              <a :href="'/estoque-estatistica/' + produto.codproduto + '?codprodutovariacao='+produto.codprodutovariacao" target="_blank" tabindex="-1">
                <q-icon name="multiline_chart" />
              </a>
            </q-item-tile>
            <q-item-tile sublabel lines="1">
              {{ produto.variacao }}
            </q-item-tile>
          </q-item-main>


          <q-item-main class="col-xs-2 col-md-2 col-lg-1">
              <q-input
                v-model="produto.transferir"
                type="number"
                align="right"
                min="0"
                :step="produto.lotetransferencia"
                :max="produto.saldoquantidade_origem"
              />
          </q-item-main>


          <q-item-side right>
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
            <q-item-tile stamp>
              {{ numeral(parseFloat(produto.saldoquantidade)).format('0,0') }}
              {{ produto.um }}
            </q-item-tile>
            <!-- {{ numeral(parseFloat(produto.preco)).format('0,0.00') }} -->

            <!-- {{ numeral(parseFloat(produto.saldopercentual)).format('0,0') }} -->

            <q-item-tile :icon="bateria(produto.saldopercentual)" :color="corBateria(produto.saldopercentual)" />
            <!-- {{ numeral((parseFloat(produto.saldoquantidade) / parseFloat(produto.estoquemaximo))*100).format('0,0.00') }} -->
          </q-item-side>
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

      let requisicoes = []
      let vm = this

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

      })

      // se nenhum produto retorna erro
      if (requisicoes.length == 0) {
        this.$q.notify({
          type: 'negative',
          message: 'Nenhum produto selecionado!'
        })
        return
      }

      vm.$axios.post('transferencia/requisicao', { requisicoes }).then(function(request){
        vm.$q.notify({
          message: 'Requisição criada!',
          type: 'positive',
        })
        vm.carregaListagem()
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Erro ao salvar!',
          type: 'negative',
        })
        console.log(error)
      })

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
      this.produtos = []
      let params = {
        codestoquelocalorigem: this.filtro.codestoquelocalorigem,
        codestoquelocaldestino: this.filtro.codestoquelocaldestino
      }
      if (params.codestoquelocalorigem == null || params.codestoquelocaldestino == null) {
        return
      }
      if (params.codestoquelocalorigem == params.codestoquelocaldestino) {
        return
      }
      let vm = this
      vm.$axios.get('transferencia/produtos-faltando-sem-requisicao', { params }).then(function(request){
        request.data.forEach(function (prod) {
          prod.selecionado = false
        })
        vm.produtos = request.data
      }).catch(function(error) {
        vm.$q.notify({
          message: 'Erro ao carregar dados!',
          type: 'negative',
        })
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
