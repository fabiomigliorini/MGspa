<template>
<mg-layout drawer>
  <q-btn flat round slot="menuRight" @click="$router.push('/estoque-saldo-conferencia')">
    <q-icon name="arrow_back" />
  </q-btn>
  <template slot="title" v-if="carregado">
    {{data.local.estoquelocal }} / {{ data.marca.marca }}
  </template>

  <div slot="drawer">
    <!-- Filtra Ativos -->
    <q-list-header>Filtros</q-list-header>
    <q-item tag="label">
      <q-item-side icon="thumb_up"></q-item-side>
      <q-item-main>
        <q-item-tile title>Ativos</q-item-tile>
      </q-item-main>
      <q-item-side right>
        <q-radio v-model="filter.inativo" :val="0" />
      </q-item-side>
    </q-item>
    <!-- Filtra Inativos -->
    <q-item tag="label">
      <q-item-side icon="thumb_down">
      </q-item-side>
      <q-item-main>
        <q-item-tile title>Inativos</q-item-tile>
      </q-item-main>
      <q-item-side right>
        <q-radio v-model="filter.inativo" :val="1" />
      </q-item-side>
    </q-item>
    <!-- Filtra Ativos e Inativos -->
    <q-item tag="label">
      <q-item-side icon="thumbs_up_down">
      </q-item-side>
      <q-item-main>
        <q-item-tile title>Todos</q-item-tile>
      </q-item-main>
      <q-item-side right>
        <q-radio v-model="filter.inativo" :val="9" />
      </q-item-side>
    </q-item>
    <q-item-separator />
    <!-- Filtra por data de corte -->
    <q-list-header>Data de Corte Conferência</q-list-header>
    <q-item tag="label">
      <q-item-main v-if="">
        <q-datetime v-model="filter.dataCorte" type="date" format="DD/MMM/YYYY" />
      </q-item-main>
    </q-item>
  </div>

  <div slot="content">
    <q-tabs v-model="tabAberta">
      <q-tab slot="title" name="tabAConferir" default>
        <q-icon name="assignment_late" style="font-size: 30px" />
      </q-tab>
      <q-tab slot="title" name="tabConferido">
        <q-icon name="assignment_turned_in" style="font-size: 30px" />
      </q-tab>
    </q-tabs>
    <q-list highlight separator>
      <template v-for="produto in produtosMostrar">
        <q-item multiline @click.native="buscaProdutoPorCodvariacao(produto.codprodutovariacao)">
          <!-- <q-item-side :avatar="produto.imagem" v-if="produto.imagem" /> -->
          <q-item-side v-if="produto.imagem">
            <img :src="produto.imagem" style="width: 55px; height: 55px" />
          </q-item-side>
          <q-item-main>
            <q-item-tile label lines="3">
              {{ produto.produto }}
              <template v-if="produto.variacao">- {{produto.variacao}}</template>
            </q-item-tile>
            <q-item-tile sublabel lines="2">
              <q-chip detail square dense icon="vpn_key">
                {{ numeral(produto.codproduto).format('000000') }}
              </q-chip>
              <q-chip detail square dense icon="widgets" :color="(produto.saldo>0)?'green':(produto.saldo<0)?'red':'grey'">
                {{ numeral(produto.saldo).format('0,0') }}
              </q-chip>
              <q-chip detail square dense icon="thumb_down" v-if="produto.inativo" color="red">
                {{ moment(produto.inativo).fromNow() }}
              </q-chip>
            </q-item-tile>
          </q-item-main>
          <q-item-side right v-if="produto.ultimaconferencia">
            <q-item-tile stamp>
              {{ moment(produto.ultimaconferencia).fromNow() }}
            </q-item-tile>
            <q-item-tile icon="assignment_turned_in"/>
          </q-item-side>
        </q-item>
      </template>
    </q-list>
    <router-link :to="{ path: '/estoque-saldo-conferencia/conferencia' }">
      <q-page-sticky corner="bottom-right" :offset="[32, 32]">
        <q-btn round color="primary">
          <q-icon name="add" />
        </q-btn>
      </q-page-sticky>
    </router-link>

    <!-- MODAL DE DETALHES DO PRODUTO -->
    <q-modal v-model="modalProduto" v-if="produtoCarregado">
      <q-btn color="primary" @click="modalProduto = false" label="Fechar" align="end" />
      <q-card>

        <q-card-title>
          #{{produto.produto.codproduto}}<br/>
          {{produto.produto.produto}} - {{produto.variacao.variacao}}
          <!-- se for inativo colocar uma classe de erro -->
          <!-- se for descontinuado uma classe de warning -->
          <q-chip tag square pointing="left" color="negative" v-if="produto.produto.inativo">Inativo</q-chip>
          <q-chip tag square pointing="left" color="negative" v-if="produto.variacao.inativo">Inativo</q-chip>
          <q-chip tag square pointing="left" color="warning" v-if="produto.variacao.descontinuado">Descontinuado</q-chip>

          <span slot="subtitle">
            <!-- se a referencia da variacao for em branco mostrar a referencia do produto  -->
            <q-chip detail square dense icon="vpn_key">
              {{ numeral(produto.variacao.referencia).format('000000') }}
            </q-chip>

            <template v-if="produto.variacao.referencia == null">{{produto.produto.referencia}}</template>

            <q-chip detail square dense icon="store">
              {{produto.localizacao.estoquelocal}}
            </q-chip>

            <q-chip detail square dense icon="widgets" :color="(produto.saldoatual.quantidade>0)?'green':(produto.saldoatual.quantidade<0)?'red':'grey'">
              {{ numeral(produto.saldoatual.quantidade).format('0,0') }}
            </q-chip>

            <q-chip detail square dense icon="monetization_on">
              {{ numeral(produto.saldoatual.custo).format('($0,0)') }}
            </q-chip>

          </span>
        </q-card-title>

        <q-card-main>

          Mínimo: {{produto.localizacao.estoqueminimo}} <br />
          Máximo: {{produto.localizacao.estoquemaximo}} <br />
          Vencimento: {{produto.localizacao.vencimento}}
          <br /> Corredor: {{produto.localizacao.corredor}} <br />
           Prateleira: {{produto.localizacao.prateleira}} <br />
           Coluna: {{produto.localizacao.coluna}}<br />
           Bloco: {{produto.localizacao.bloco}}<br />
        </q-card-main>

        <q-card-separator />
        <!-- LOOP NAS CONFERENCIAS -->


        <!-- UM Q-ITEM para cada conferencia -->
        <q-scroll-area style="height: 50vh;">
          <template v-for="conferencia in produto.conferencias">
            <q-list>
              <q-item>
                <q-item-main>
                  <q-item-tile label>
                    Criado em: {{conferencia.criacao}} <br />
                    Quantidade informada: {{conferencia.quantidadeinformada}} <br />
                    Quantidade sistema: {{conferencia.quantidadesistema}} <br />
                    Custo medio:{{conferencia.customedioinformado}} <br />
                    Custo do sistema: {{conferencia.custosistema}} <br />
                  </q-item-tile>
                  <q-item-tile sublabel>
                    Por: {{conferencia.usuario}}<br/>
                    Data: {{conferencia.data}}<br/>
                    Obs: {{conferencia.observacoes}}<br/>
                  </q-item-tile>
                </q-item-main>
                <q-item-side right>
                  <q-btn flat round color="negative" icon="thumb_down"></q-btn>
                </q-item-side>
              </q-item>
            </q-list>
          </template>
        </q-scroll-area>



      </q-card>
    </q-modal>
  </div>
</mg-layout>
</template>

<script>
import MgSelectEstoqueLocal from '../../utils/select/MgSelectEstoqueLocal'
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'
import MgAutocompleteMarca from '../../utils/autocomplete/MgAutocompleteMarca'

export default {
  name: 'estoque-saldo-conferencia-listagem',
  components: {
    MgLayout,
    MgSelectEstoqueLocal,
    MgErrosValidacao,
    MgAutocompleteMarca
  },
  data() {
    return {
      data: {},
      filter: {
        codestoquelocal: null,
        codmarca: null,
        fiscal: 1,
        data: null,
        inativo: 1,
        dataCorte: null
      },
      carregado: false,

      tabAberta: 'tabAConferir',

      produto: {},
      modalProduto: false,
      produtoCarregado: false
    }
  },
  watch: {
    filter: {
      handler: function(val, oldVal) {
        this.buscaListagem(false, null)
      },
      deep: true
    }
  },
  computed: {
    produtosMostrar: function() {
      if (!this.carregado) {
        return
      }
      let vm = this

      switch (vm.tabAberta) {

        case 'tabAConferir':
          return vm.data.produtos.filter(function(produto) {
            return (produto.ultimaconferencia == null) ||
              (vm.moment(produto.ultimaconferencia).isBefore(vm.filter.dataCorte))
          })
          break

        default:
          return vm.data.produtos.filter(function(produto) {
            return (produto.ultimaconferencia != null) &&
              (!vm.moment(produto.ultimaconferencia).isBefore(vm.filter.dataCorte))
          })
      }
    },
    header: {
      get() {
        return this.$store.state.estoqueSaldoConferencia.estoqueSaldoConferenciaState
      }
    }
  },
  methods: {

    buscaListagem: function() {
      let vm = this
      let params = {
        codestoquelocal: vm.filter.codestoquelocal,
        codmarca: vm.filter.codmarca,
        fiscal: vm.filter.fiscal,
        inativo: vm.filter.inativo
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-listagem', {
        params
      }).then(function(request) {
        vm.data = request.data
        vm.carregado = true
      }).catch(function(error) {
        console.log(error.response)
      })
    },

    buscaProdutoPorCodvariacao: function(codprodutovariacao) {
      let vm = this
      let params = {
        codprodutovariacao: codprodutovariacao,
        codestoquelocal: vm.filter.codestoquelocal,
        fiscal: vm.filter.fiscal
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-produto', {
        params
      }).then(function(request) {
        vm.produto = request.data
        vm.modalProduto = true
        vm.produtoCarregado = true
      }).catch(function(error) {
        vm.modalProduto = false
        vm.produtoCarregado = false
        console.log(error.response)
      })
    }
  },
  mounted() {
    this.buscaListagem()
  },
  created() {
    this.filter.codestoquelocal = this.$route.params.codestoquelocal
    this.filter.codmarca = this.$route.params.codmarca
    this.filter.fiscal = this.$route.params.fiscal
    this.filter.data = new Date(parseInt(this.$route.params.data))
    this.filter.dataCorte = this.moment().startOf('day').subtract(15, 'days').toDate()
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
