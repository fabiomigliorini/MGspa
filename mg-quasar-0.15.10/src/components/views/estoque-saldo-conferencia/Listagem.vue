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
        <q-item multiline @click.native="buscaProdutoPorCodvariacao(produto)">
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
        <q-item-tile icon="assignment_turned_in" />
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

      <q-card class="bigger">
        <q-card-media>
          <center>
            <img :src="produtoImagem" style="max-width:300px; height: auto">
          </center>
        </q-card-media>
        <q-card-title class="relative-position">
          <!-- <q-btn fab color="primary" icon="place" class="absolute" style="top: 0; right: 8px; transform: translateY(-50%);" /> -->
          <div>
            {{produto.produto.produto}}
          </div>
          <div slot="subtitle">
            {{produto.variacao.variacao}}
          </div>
          <!-- <q-rating slot="subtitle" v-model="stars" :max="5" /> -->
          <div slot="right" class="row items-center">
            <q-icon name="vpn key" />&nbsp;
            {{ numeral(produto.produto.codproduto).format('000000') }}
          </div>
        </q-card-title>
        <q-card-main>
          <p class="text-faded">
            R$ {{ numeral(parseFloat(produto.produto.preco)).format('0,0.00') }}・Preço de Venda <br />

            <span v-if="produto.variacao.referencia">
              {{ produto.variacao.referencia }}
              ・Referência
              <br />
            </span>
            <span v-else-if="produto.produto.referencia">
              {{ produto.produto.referencia }}
              ・Referência
              <br />
            </span>

            <q-chip square color="negative" v-if="produto.produto.inativo">
              Produto Inativo há tantos dias
            </q-chip>
            <q-chip square color="negative" v-if="produto.variacao.inativo">
              Variação Inativa há tantos tempo
            </q-chip>
            <q-chip square color="negative" v-if="produto.variacao.descontinuado">
              descontinuado há tantos tempo
            </q-chip>

          </p>
        </q-card-main>
        <q-card-separator />
        <q-list>
          <q-item>
            <q-item-side>
              <q-item-tile color="primary" icon="widgets" />
            </q-item-side>
            <q-item-main>
              <q-item-tile label>
                <b>
                  {{ numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0') }}
                </b>
                {{ produto.produto.siglaunidademedida }}
                em estoque
              </q-item-tile>
              <q-item-tile sublabel>
                Suegerido entre {{ numeral(parseFloat(produto.variacao.estoqueminimo)).format('0,0') }} e
                {{ numeral(parseFloat(produto.variacao.estoquemaximo)).format('0,0') }}
                {{ produto.produto.siglaunidademedida }}.
              </q-item-tile>
            </q-item-main>
          </q-item>
          <q-item>
            <q-item-side>
              <q-item-tile color="primary" icon="attach money" />
            </q-item-side>
            <q-item-main>
              <q-item-tile label>
                R$
                <b>{{ numeral(parseFloat(produto.saldoatual.custo)).format('0,0.000000') }}</b>
              </q-item-tile>
              <q-item-tile sublabel>Custo de cada {{ produto.produto.unidademedida }}.</q-item-tile>
            </q-item-main>
          </q-item>
          <q-item>
            <q-item-side>
              <q-item-tile color="red" icon="place" />
            </q-item-side>
            <q-item-main>
              <q-item-tile label>
                {{ produto.localizacao.estoquelocal }}
              </q-item-tile>
              <q-item-tile sublabel v-if="produto.localizacao.corredor">
                Corredor&nbsp{{ numeral(produto.localizacao.corredor).format('00') }}
                Prateleira&nbsp{{ numeral(produto.localizacao.prateleira).format('00') }}
                Coluna&nbsp{{ numeral(produto.localizacao.coluna).format('00') }}
                Bloco&nbsp{{ numeral(produto.localizacao.bloco).format('00') }}
              </q-item-tile>
            </q-item-main>
          </q-item>
          <q-item>
            <q-item-side>
              <q-item-tile color="amber" icon="fingerprint" />
            </q-item-side>
            <q-item-main>
              <q-item-tile label>
                Códigos de Barras
              </q-item-tile>
              <q-item-tile sublabel>
                <template v-for="barra in produto.barras">
                  {{ barra.barras }} {{ barra.siglaunidademedida }}
                  <template v-if="barra.quantidade > 1">
                     C/{{ parseInt(barra.quantidade) }}
                  </template>
                  <br />
                </template>
              </q-item-tile>
            </q-item-main>
          </q-item>
        </q-list>
        <q-scroll-area style="height: 400px;">
          <q-timeline color="secondary" style="padding: 0 24px;">
            <q-timeline-entry heading>Conferências Efetuadas</q-timeline-entry>

            <q-timeline-entry title="Event Title" subtitle="February 22, 1986" side="right">
              <div>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor
                in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
              </div>
            </q-timeline-entry>

            <q-timeline-entry title="Event Title" subtitle="February 21, 1986" side="right" icon="delete">
              <div>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor
                in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
              </div>
            </q-timeline-entry>

            <q-timeline-entry heading>November, 2017</q-timeline-entry>

            <q-timeline-entry title="Event Title" subtitle="February 22, 1986" side="right">
              <div>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor
                in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
              </div>
            </q-timeline-entry>

            <q-timeline-entry title="Event Title" subtitle="February 22, 1986" side="right">
              <div>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor
                in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
              </div>
            </q-timeline-entry>

            <q-timeline-entry title="Event Title" subtitle="February 22, 1986" side="left" color="orange" icon="done_all">
              <div>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor
                in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
              </div>
            </q-timeline-entry>

            <q-timeline-entry title="Event Title" subtitle="February 22, 1986" side="right">
              <div>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor
                in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
              </div>
            </q-timeline-entry>

            <q-timeline-entry title="Event Title" subtitle="February 22, 1986" side="left">
              <div>
                Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor
                in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
              </div>
            </q-timeline-entry>
          </q-timeline>
        </q-scroll-area>
        <q-card-actions>
          <q-btn flat color="primary" label="Fazer Nova Conferência"/>
          <q-btn flat @click="modalProduto = false" label="Fechar"/>
        </q-card-actions>
      </q-card>



      <q-card-title class="relative-position">
        <q-chip style="top: 0; transform: translateY(-100%);" square color="negative" v-if="produto.produto.inativo">Inativo</q-chip>
        <q-chip style="top: 0; transform: translateY(-100%);" square color="negative" v-if="produto.variacao.inativo">Variação Inativa</q-chip>
        <q-chip style="top: 0; transform: translateY(-100%);" color="warning" v-if="produto.variacao.descontinuado">Variação descontinuada</q-chip>

        <div class="ellipsis">
          <q-chip dense detail square icon="vpn_key">
            {{ numeral(produto.produto.codproduto).format('000000') }}
          </q-chip>
          {{produto.produto.produto}} <template v-if="produto.variacao.variacao">- {{produto.variacao.variacao}}</template>
        </div>

        <q-chip detail square icon="vpn_key" v-if="produto.variacao.referencia">
          var.ref - {{ numeral(produto.variacao.referencia).format('000000') }}
        </q-chip>

      </q-card-title>
      <q-card-separator />
      <q-card-actions>

        <q-chip dense detail square icon="vpn_key" v-if="produto.variacao.referencia == null">
          {{ numeral(produto.produto.referencia).format('000000') }}
        </q-chip>


      </q-card-actions>
      <q-card-actions>

        <q-chip dense detail square icon="store">
          {{produto.localizacao.estoquelocal}}
        </q-chip>

        <q-chip dense detail square icon="monetization_on">
          {{ numeral(parseFloat(produto.saldoatual.custo)).format('0,0.000000') }}
        </q-chip>
        <q-chip dense detail square style="" icon="widgets" :color="(produto.saldoatual.quantidade>0)?'green':(produto.saldoatual.quantidade<0)?'red':'grey'">
          {{ numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0') }}
        </q-chip>

        <q-chip dense detail square icon="arrow_downward">
          {{produto.localizacao.estoqueminimo}}10
        </q-chip>

        <q-chip dense detail square icon="arrow_upward">
          {{produto.localizacao.estoquemaximo}}20
        </q-chip>

      </q-card-actions>
      <q-card-actions vertical>

        <q-chip dense detail square>
          Vencimento: {{produto.localizacao.vencimento}}
        </q-chip>

        <q-chip dense detail square>
          Corredor: {{produto.localizacao.corredor}}
        </q-chip>

        <q-chip dense detail square>
          Prateleira: {{produto.localizacao.prateleira}}
        </q-chip>

        <q-chip dense detail square>
          Coluna: {{produto.localizacao.coluna}}
        </q-chip>

        <q-chip dense detail square>
          Bloco: {{produto.localizacao.bloco}}
        </q-chip>

      </q-card-actions>
      <q-card-separator />

      <q-card-main>
        <q-scroll-area style="height: 50vh;">
          <template v-for="conferencia in produto.conferencias">
              <q-list style="padding-top:20px">
                <q-item>
                  <q-btn class="absolute" style="top: 0; right: 0px; transform: translateY(-70%);" flat round color="negative" icon="thumb_down"></q-btn>

                  <q-item-main>

                    <q-item-tile label>
                      <q-chip small detail square  icon="date_range">
                        {{ moment(conferencia.criacao).fromNow() }}
                      </q-chip><br />
                      Informado: {{conferencia.quantidadeinformada}} <br />
                      Sistema: {{conferencia.quantidadesistema}} <br />
                      Custo medio:{{conferencia.customedioinformado}} <br />
                      Custo do sistema: {{conferencia.custosistema}} <br />
                    </q-item-tile>

                    <q-item-tile sublabel>

                      <q-chip small detail square  icon="account_box">
                        {{conferencia.usuario}}
                      </q-chip>

                      <q-chip small detail square  icon="date_range">
                        {{ moment(conferencia.data).fromNow() }}
                      </q-chip><br />

                      Obs: {{conferencia.observacoes}}<br/>
                    </q-item-tile>
                  </q-item-main>

                </q-item>
              </q-list>
            </template>
        </q-scroll-area>
      </q-card-main>

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
        inativo: 0,
        dataCorte: null
      },
      carregado: false,

      tabAberta: 'tabAConferir',

      produto: {},
      produtoImagem: null,
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

    buscaProdutoPorCodvariacao: function(produto) {
      let vm = this
      this.produtoImagem = produto.imagem
      let params = {
        codprodutovariacao: produto.codprodutovariacao,
        codestoquelocal: vm.filter.codestoquelocal,
        fiscal: vm.filter.fiscal
      }
      vm.$axios.get('estoque-saldo-conferencia/busca-produto', {
        params
      }).then(function(request) {
        //console.log(vm.produto)
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
