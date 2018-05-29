<template>
  <mg-layout drawer back-path="/estoque-saldo-conferencia/">

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
        <q-item-main>
          <q-input type="date" v-model="filter.dataCorte" align="center" clearable />
        </q-item-main>
      </q-item>
    </div>

    <template slot="tabHeader">
      <q-tabs v-model="filter.conferidos">
        <q-tab slot="title" name="conferir" icon="close" label="Por fazer" default>
          <!-- <q-icon name="assignment_late" style="font-size: 30px" /> -->
        </q-tab>
        <q-tab slot="title" name="conferidos" icon="check" label="Conferidos">
          <!-- <q-icon name="assignment_turned_in" style="font-size: 30px" /> -->
        </q-tab>
      </q-tabs>
    </template>

    <div slot="content">

      <!-- Infinite scroll -->
      <template v-if="carregado">

        <q-list highlight separator v-if="data.produtos.length > 0">
          <q-infinite-scroll :handler="loadMore" ref="infiniteScroll">
            <template v-for="produto in data.produtos">
              <q-item multiline @click.native="buscaProduto(produto)">
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
                    <q-chip detail square dense icon="widgets" :color="(produto.saldoquantidade>0)?'green':(produto.saldoquantidade<0)?'red':'grey'">
                      {{ numeral(produto.saldoquantidade).format('0,0') }}
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

              <q-item-separator />
            </template>

          </q-infinite-scroll>
        </q-list>
        <template v-else>
          <q-item>
            <q-item-main>
              <h3 v-if="filter.conferidos =='conferidos'" class="text-red text-center">
                Nenhum produto conferido! <br />
                <q-icon name="thumb down" size="25vh"/>
              </h3>
              <h3 v-else class="text-green text-center">
                Nenhum produto para conferir! <br />
                <q-icon name="thumb up" size="25vh"/>
              </h3>
            </q-item-main>
          </q-item>
        </template>

      </template>
      <q-page-sticky corner="bottom-right" :offset="[32, 32]">
        <q-btn round color="primary" icon="add" @click.native="modalBuscaPorBarras = true" />
      </q-page-sticky>

    <!-- MODAL DE DETALHES DO PRODUTO -->
    <div class="col-md-12 col-lg-12">
      <q-modal v-model="modalProduto" v-if="produtoCarregado" maximized>
        <div class="row">
          <!-- COLUNA 1 -->
          <div class="col-sm-12 col-md-5 col-lg-5">
            <q-card-media>
              <center>
                <img :src="produtoImagem" style="max-width:95vw; max-height:80vh; height: auto">
              </center>
            </q-card-media>
            <q-card-title>
              <q-item-tile>
                {{produto.produto.produto}} {{produto.variacao.variacao}}
                <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.produto.inativo">
                  Produto Inativo {{ moment(produto.produto.inativo).fromNow() }}
                </q-chip>
                <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.variacao.inativo">
                  Variação Inativa {{ moment(produto.variacao.inativo).fromNow() }}
                </q-chip>
                <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.variacao.descontinuado">
                  Descontinuado {{ moment(produto.variacao.descontinuado).fromNow() }}
                </q-chip>
              </q-item-tile>
            </q-card-title>
          </div>

          <!-- COLUNA 2 -->
          <div class="col-sm-12 col-md-7 col-lg-7">
            <div class="row">

              <div class="col-sm-12 col-md-4">
                <q-list no-border>
                  <q-item>
                    <q-item-side>
                      <q-item-tile color="primary" icon="widgets" />
                    </q-item-side>
                    <q-item-main>
                      <q-item-tile label>
                        <b>{{ numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0') }}</b> {{ produto.produto.siglaunidademedida }} em estoque
                      </q-item-tile>
                      <q-item-tile sublabel>
                        Suegerido entre {{ numeral(parseFloat(produto.variacao.estoqueminimo)).format('0,0') }} e {{ numeral(parseFloat(produto.variacao.estoquemaximo)).format('0,0') }} {{ produto.produto.siglaunidademedida }}.
                      </q-item-tile>
                    </q-item-main>
                  </q-item>
                  <q-item>
                    <q-item-side>
                      <q-item-tile color="primary" icon="attach_money" />
                    </q-item-side>
                    <q-item-main>
                      <q-item-tile label>
                        Vendido à R$
                        <b>{{ numeral(parseFloat(produto.produto.preco)).format('0,0.00') }}</b>
                      </q-item-tile>
                      <q-item-tile sublabel>
                        Cada {{ produto.produto.unidademedida }} custa R$
                        <b>{{ numeral(parseFloat(produto.saldoatual.custo)).format('0,0.00') }}</b>.
                      </q-item-tile>
                    </q-item-main>
                  </q-item>
                </q-list>
              </div>
              <div class="col-sm-12 col-md-4">
                <q-list no-border>
                  <q-item>
                    <q-item-side>
                      <q-item-tile color="green" icon="place" />
                    </q-item-side>
                    <q-item-main>
                      <q-item-tile label>
                        {{ produto.localizacao.estoquelocal }}
                      </q-item-tile>
                      <q-item-tile sublabel v-if="produto.localizacao.corredor">
                        Corredor&nbsp{{ numeral(produto.localizacao.corredor).format('00') }} Prateleira&nbsp{{ numeral(produto.localizacao.prateleira).format('00') }} Coluna&nbsp{{ numeral(produto.localizacao.coluna).format('00') }} Bloco&nbsp{{ numeral(produto.localizacao.bloco).format('00')
                        }}
                      </q-item-tile>
                    </q-item-main>
                  </q-item>
                  <q-item v-if="produto.localizacao.vencimento">
                    <q-item-side>
                      <q-item-tile color="red" icon="access alarm" />
                    </q-item-side>
                    <q-item-main>
                      <q-item-tile label v-if="moment(produto.localizacao.vencimento).isAfter()">
                        Vence {{ moment(produto.localizacao.vencimento).fromNow() }}
                      </q-item-tile>
                      <q-item-tile label v-else color="red">
                        Vencido {{ moment(produto.localizacao.vencimento).fromNow() }}
                      </q-item-tile>
                      <q-item-tile sublabel>
                        {{ moment(produto.localizacao.vencimento).format('dddd, LL') }}
                      </q-item-tile>
                    </q-item-main>
                  </q-item>
                </q-list>
              </div>
              <div class="col-sm-12 col-md-4">
                <q-list no-border>
                  <q-item>
                    <q-item-side>
                      <q-item-tile color="black" icon="vpn_key" />
                    </q-item-side>
                    <q-item-main>
                      <q-item-tile label>
                        {{ numeral(produto.produto.codproduto).format('000000') }}
                      </q-item-tile>
                    </q-item-main>
                  </q-item>
                  <q-item>
                    <q-item-side>
                      <q-item-tile color="black" icon="local offer" />
                    </q-item-side>
                    <q-item-main>
                      <q-item-tile label>
                        <template v-if="produto.variacao.referencia">
                            {{ produto.variacao.referencia }}
                          </template>
                        <template v-else-if="produto.produto.referencia">
                            {{ produto.produto.referencia }}
                          </template>
                      </q-item-tile>
                    </q-item-main>
                  </q-item>
                  <q-item>
                    <q-item-side>
                      <q-item-tile color="black" icon="view column" />
                    </q-item-side>
                    <q-item-main>
                      <q-item-tile sublabel>
                        <small>
                            <template v-for="barra in produto.barras">
                              {{ barra.barras }} {{ barra.siglaunidademedida }}
                              <template v-if="barra.quantidade > 1">
                                C/{{ parseInt(barra.quantidade) }}
                              </template><br />
                            </template>
                          </small>
                      </q-item-tile>
                    </q-item-main>
                  </q-item>
                </q-list>
              </div>
            </div>
            <q-timeline color="secondary" style="padding: 0 24px;" v-if="produto.conferencias.length > 0">
              <q-timeline-entry subtitle="Últimas conferências" side="left" />
              <q-timeline-entry v-for="(conf, iconf) in produto.conferencias" :key="iconf" :title="numeral(parseFloat(conf.quantidadeinformada)).format('0,0') + ' '
                + produto.produto.siglaunidademedida + ' custando R$ '
                + numeral(parseFloat(conf.customedioinformado)).format('0,0.00')
                + ' cada ' + produto.produto.unidademedida" :subtitle="conf.usuario + ' ' + moment(conf.criacao).fromNow()" :side="((iconf%2)==0)?'right':'left'">
                <small class="text-faded">
                  O saldo do sistema no momento da conferência era de
                  <b>{{ numeral(parseFloat(conf.quantidadesistema)).format('0,0') }}</b> {{produto.produto.siglaunidademedida}}
                  custando R$ <b>{{numeral(parseFloat(conf.customediosistema)).format('0,0.00')}}</b>
                  cada {{ produto.produto.unidademedida }}.
                </small>
                <small class="text-faded" v-if="conf.observacoes">
                  <br />
                  {{conf.observacoes}}
                </small>
                <q-btn round flat dense icon="thumb_down" color="red" @click.native="inativarConferencia(conf.codestoquesaldoconferencia)" />
              </q-timeline-entry>
            </q-timeline>
          </div>
        </div>
        <q-page-sticky position="bottom-right" :offset="[32, -18]">
          <q-btn round color="primary" icon="add" @click="modalConferencia = true" />
        </q-page-sticky>
        <q-page-sticky position="top-right" :offset="[32, -18]">
          <q-btn round color="faded" icon="close" @click="modalProduto = false" />
        </q-page-sticky>
      </q-modal>
    </div>

    <!-- MODAL DE CONFERÊNCIA DO PRODUTO -->
    <template>
      <q-modal maximized v-model="modalConferencia" v-if="produtoCarregado" @hide="focoCampoBarras()" @show="focoCampoQuantidadeInformada()">
        <div class="row justify-center">
          <div class="col-xs-12 col-sm-6 col-md-5 col-lg-4">
            <q-card-title>

              <!-- Nome do Produto e Variação -->
              <q-item-tile>
                {{produto.produto.produto}} {{produto.variacao.variacao}}
                <template v-if="produto.produto.variacao">- {{produto.produto.variacao}}</template>

                <!-- Ativo / Inativo / Descontinuado -->
                <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.produto.inativo">
                  Produto Inativo {{ moment(produto.produto.inativo).fromNow() }}
                </q-chip>
                <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.variacao.inativo">
                  Variação Inativa {{ moment(produto.variacao.inativo).fromNow() }}
                </q-chip>
                <q-chip detail square dense icon="thumb_down"  square color="red" v-if="produto.variacao.descontinuado">
                  Descontinuado {{ moment(produto.variacao.descontinuado).fromNow() }}
                </q-chip>
              </q-item-tile>

              <!-- Código do Produto -->
              <q-chip detail square dense icon="vpn_key">
                {{ numeral(produto.produto.codproduto).format('000000') }}
              </q-chip>
            </q-card-title>

            <q-card-main>
              <form>

                  <!-- QUANTIDADE -->
                  <q-item dense>
                    <q-item-side icon="widgets" color="blue"/>
                    <q-item-main>
                      <q-field :helper="'Quantidade Atual: ' + numeral(parseFloat(produto.saldoatual.quantidade)).format('0,0')">
                        <q-input required type="number" v-model="conferencia.quantidade" float-label="Quantidade" :decimals="3" autofocus align="right" ref="campoQuantidadeInformada" clearable @keydown.enter="salvaConferencia()"/>
                      </q-field>
                    </q-item-main>
                  </q-item>

                  <!-- CUSTO -->
                  <q-item dense>
                    <q-item-side icon="attach_money" color="blue"/>
                    <q-item-main>
                      <q-field :helper="'O custo atual é R$ ' + numeral(parseFloat(produto.saldoatual.custo)).format('0,0.00')">
                        <q-input required type="number" v-model="conferencia.customedio" float-label="Custo" :decimals="6" align="right" clearable  @keydown.enter="salvaConferencia()"  ref="campoCustoMedioInformado"/>
                      </q-field>
                    </q-item-main>
                  </q-item>

                  <!-- VENCIMENTO -->
                  <q-item dense>
                    <q-item-side icon="access alarm" color="red"/>
                    <q-item-main>
                      <q-field >
                        <q-input type="date" v-model="conferencia.vencimento" stack-label="Vencimento" align="center" clearable />
                      </q-field>
                    </q-item-main>
                  </q-item>

                  <!-- BOTAO LOCALIZACAO -->
                  <q-item dense>
                    <q-item-side icon="place" color="green"/>
                    <q-item-main>
                      <q-field>
                        <div class="row gutter-xs">
                          <div class="col">
                            <q-input v-model="conferencia.corredor" float-label="Corredor" type="number" :decimals="0" min="0" max="99" align="center" clearable @keydown.enter="salvaConferencia()"/>
                          </div>
                          <div class="col">
                            <q-input v-model="conferencia.prateleira" float-label="Prateleira" type="number" :decimals="0" min="0" max="99" align="center" clearable @keydown.enter="salvaConferencia()"/>
                          </div>
                          <div class="col">
                            <q-input v-model="conferencia.coluna" float-label="Coluna" type="number" :decimals="0" min="0" max="99" align="center" clearable @keydown.enter="salvaConferencia()"/>
                          </div>
                          <div class="col">
                            <q-input v-model="conferencia.bloco" float-label="Bloco" type="number" :decimals="0" min="0" max="99" align="center" clearable @keydown.enter="salvaConferencia()"/>
                          </div>
                        </div>
                      </q-field>
                    </q-item-main>
                  </q-item>

                  <!-- OBSERVACOES -->
                  <q-item dense>
                    <q-item-side icon="description" color="black"/>
                    <q-item-main>
                      <q-field >
                        <q-input type="textarea" v-model="conferencia.observacoes" float-label="Observações" clearable />
                      </q-field>
                    </q-item-main>
                  </q-item>
                </form>

                <!-- BOTAO FECHAR -->
                <q-page-sticky position="top-right" :offset="[32, -18]">
                  <q-btn round color="faded" icon="close" @click="modalConferencia = false" />
                </q-page-sticky>
                <!-- BOTAO CONFIRMAR -->
                <q-page-sticky position="bottom-right" :offset="[32, -18]">
                  <q-btn round color="primary" icon="done" @click="salvaConferencia()" />
                </q-page-sticky>

              </q-card-main>

            </div>
          </div>
        </q-modal>
      </template>

      <!-- MODAL DE BUSCA POR BARRAS -->
      <template>
        <q-modal maximized v-model="modalBuscaPorBarras" @show="focoCampoBarras()">
          <div class="row justify-center " style="padding-top: 20vh">
            <div class="col-xs-10 col-sm-6 col-md-5 col-lg-4 col-xl-3 gutter-sx">
              <form @submit.prevent="buscaProduto()">
                <q-item>
                  <q-item-side icon="view column" color="black"/>
                  <q-item-main>
                    <q-field >
                      <q-input clearable v-model="buscaPorBarras" float-label="Informe o código de barras" ref="campoBarras" align="center" />
                    </q-field>
                  </q-item-main>
                </q-item>
              </form>
              <q-page-sticky position="top-right" :offset="[32, -30]">
                <q-btn round color="faded" icon="close" @click="modalBuscaPorBarras = false" />
              </q-page-sticky>
              <q-page-sticky position="bottom-right" :offset="[32, -30]">
                <q-btn round color="primary" icon="done" @click="buscaProduto()" />
              </q-page-sticky>
            </div>
          </div>
        </q-modal>
      </template>
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
      page: 1,
      filter: {
        codestoquelocal: null,
        codmarca: null,
        fiscal: 1,
        data: null,
        inativo: 0,
        conferidos: 'conferir',
        dataCorte: null
      },
      erros: {
        quantidade: null,
        customedio: null
      },
      buscaPorBarras: null,
      produto: {},
      data: {},
      conferencia: {
        quantidade: null,
        customedio: null,
        vencimento: null,
        observacoes: null,
        corredor: null,
        prateleira: null,
        coluna: null,
        bloco: null

      },
      carregado: false,
      codigoproduto: null,
      produtoImagem: null,
      modalProduto: false,
      modalConferencia: false,
      modalBuscaPorBarras: false,
      produtoCarregado: false,
      conferenciaSalva: false
    }
  },
  watch: {

    // observa filtro, sempre que alterado chama a api
    filter: {
      handler: function (val, oldVal) {
        this.page = 1
        this.buscaListagem(false, null)
      },
      deep: true
    }

  },
  methods: {
    // scroll infinito - carregar mais registros
    loadMore (index, done) {
      this.page++
      this.buscaListagem(true, done)
    },

    salvaConferencia: function() {
      let vm = this

      if (vm.conferencia.quantidade == null) {
        vm.$q.notify({
          message: 'Quantidade deve ser preenchida!',
          type: 'negative',
        })
        // jogar foco na quantidade
        vm.focoCampoQuantidadeInformada()
        return
      }

      if (vm.conferencia.customedio == null) {
        vm.$q.notify({
          message: 'Custo deve ser preenchido!',
          type: 'negative',
        })
        // jogar foco na quantidade
        vm.focoCampoCustoMedioInformado()
        return
      }

      let params = {
        codprodutovariacao: vm.produto.variacao.codprodutovariacao,
        codestoquelocal: vm.filter.codestoquelocal,
        fiscal: parseInt(vm.filter.fiscal),
        quantidadeinformada: vm.conferencia.quantidade,
        customedioinformado: vm.conferencia.customedio,
        data: vm.filter.data,
        observacoes: vm.conferencia.observacoes,
        vencimento: vm.conferencia.vencimento,
        corredor: vm.conferencia.corredor,
        prateleira: vm.conferencia.prateleira,
        coluna: vm.conferencia.coluna,
        bloco: vm.conferencia.bloco
      }
      vm.$axios.post('estoque-saldo-conferencia', params).then(function(request) {
        vm.modalConferencia = false
        vm.conferenciaSalva = true
        vm.parseProduto(request.data)
        vm.$q.notify({
          message: 'Conferência realizada',
          type: 'positive',
        })
      }).catch(function(error) {
        console.log(error)
      })
    },

    inativarConferencia: function(codestoquesaldoconferencia) {
      let vm = this
      vm.$q.dialog({
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?',
        ok: 'Inativar',
        cancel: 'Cancelar'
      }).then(() => {
        vm.$axios.post('estoque-saldo-conferencia/' + codestoquesaldoconferencia + '/inativo').then(function(request) {
          vm.parseProduto(request.data)
          vm.$q.notify({
            message: 'Conferência Inativada',
            type: 'warning',
          })
        }).catch(function(error) {
          console.log(error)
          vm.erros = error.response.data.erros
        })
      })
    },

    buscaListagem: function(concat, done) {

      // inicializa variaveis
      let vm = this
      vm.carregado = false
      
      let params = {
        codestoquelocal: vm.filter.codestoquelocal,
        codmarca: vm.filter.codmarca,
        fiscal: vm.filter.fiscal,
        inativo: vm.filter.inativo,
        dataCorte: vm.filter.dataCorte,
        conferidos: (vm.filter.conferidos=='conferidos')?1:0,
      }
      params.page = this.page
      this.loading = true

      vm.$axios.get('estoque-saldo-conferencia/busca-listagem', {
        params
      }).then(function(request) {

        // Se for para concatenar, senao inicializa
        if (vm.page == 1) {
          vm.data = request.data
        }
        else {
          vm.data.produtos = vm.data.produtos.concat(request.data.produtos)
        }

        vm.carregado = true

        // Desativa Scroll Infinito se chegou no fim
        if (vm.$refs.infiniteScroll) {
          if (request.data.produtos.length === 0) {
            vm.$refs.infiniteScroll.stop()
          }
          else {
            vm.$refs.infiniteScroll.resume()
          }
        }

        // desmarca flag de carregando
        vm.loading = false

        // Executa done do scroll infinito
        if (done) {
          done()
        }

      }).catch(function(error) {
        console.log(error)
      })
    },

    parseProduto: function(data) {
      // armazena os dados retornados pela api como variavel local
      this.produto = data

      // copia os dados do produto como default no preenchimento da conferência
      this.conferencia.vencimento = this.produto.localizacao.vencimento
      if (this.conferencia.vencimento) {
        this.conferencia.vencimento = this.moment(this.conferencia.vencimento).format('YYYY-MM-DD')
      }
      //this.conferencia.quantidade = this.produto.saldoatual.quantidade
      this.conferencia.quantidade = null
      this.conferencia.customedio = this.produto.saldoatual.custo
      this.conferencia.corredor = this.produto.localizacao.corredor
      this.conferencia.prateleira = this.produto.localizacao.prateleira
      this.conferencia.coluna = this.produto.localizacao.coluna
      this.conferencia.bloco = this.produto.localizacao.bloco
      this.conferencia.observacoes = null

      // procura registro na listagem de produtos
      var cod = this.produto.variacao.codprodutovariacao
      var i = this.data.produtos.findIndex(k => k.codprodutovariacao==cod)

      // se nao estiver no array
      if (i == -1) {

        // se for um novo item conferido adiciona
        if (this.filter.conferidos == 'conferidos' && this.moment(this.produto.saldoatual.ultimaconferencia).isAfter(this.filter.dataCorte)) {

          let novoproduto = {
            codproduto: this.produto.produto.codproduto,
            codprodutoimagem: null,
            codprodutoimagemvariacao: null,
            codprodutovariacao: this.produto.variacao.codprodutovariacao,
            descontinuado: this.produto.variacao.descontinuado,
            imagem: null,
            inativo: (this.produto.produto.inativo != null)?this.produto.produto.inativo:this.produto.variacao.inativo,
            produto: this.produto.produto.produto,
            saldoquantidade: this.produto.saldoatual.quantidade,
            ultimaconferencia: this.produto.saldoatual.ultimaconferencia,
            variacao: this.produto.variacao.variacao
          }

          this.data.produtos.unshift(novoproduto)

        // se nao nao faz nada
        } else {
          return
        }
      }

      // remove registro da listagem se está na aba de *conferidos*
      // e data da *conferencia nao e posterior a data de corte*
      if (this.filter.conferidos == 'conferidos'
          && (!this.moment(this.produto.saldoatual.ultimaconferencia).isAfter(this.filter.dataCorte))) {
        this.data.produtos.splice(i, 1)
        return

      // remove registro da listagem se está na aba de produtos *a conferir*
      // e a data de *conferencia e posterior a data de corte*
      } else if (this.filter.conferidos != 'conferidos'
          && this.moment(this.produto.saldoatual.ultimaconferencia).isAfter(this.filter.dataCorte)) {
        this.data.produtos.splice(i, 1)
        return
      }

      // atualiza dados na listagem
      this.data.produtos[i].saldoquantidade = parseFloat(this.produto.saldoatual.quantidade)
      this.data.produtos[i].ultimaconferencia = this.produto.saldoatual.ultimaconferencia

    },

    buscaProduto: function(produto) {
      let vm = this
      var params = {
        barras: null,
        codprodutovariacao: null,
        codestoquelocal: this.filter.codestoquelocal,
        fiscal: this.filter.fiscal
      }
      if (produto == null) {
        if (vm.buscaPorBarras == null) {
          return
        }
        params.barras = vm.buscaPorBarras
      } else {
        this.produtoImagem = produto.imagem
        params.codprodutovariacao = produto.codprodutovariacao
      }
      this.buscaPorBarras = null

      vm.$axios.get('estoque-saldo-conferencia/busca-produto', { params }).then(function(request){
        if (request.data.erro == true) {
          vm.$q.notify({
            message: request.data.mensagem,
            type: 'negative',
          })
          return
        }
        vm.parseProduto(request.data)
        vm.produtoCarregado = true
        if (produto == null) {
          vm.modalConferencia = true
        } else {
          vm.modalProduto = true
        }

      }).catch(function(error) {
        console.log(error)
        if (produto == null) {
          vm.modalConferencia = false
        } else {
          vm.modalProduto = false
        }
        vm.produtoCarregado = false
      })
    },
    focoCampoBarras: function() {
      this.$nextTick(() => this.$refs.campoBarras.focus())
    },
    focoCampoQuantidadeInformada: function() {
      this.$nextTick(() => this.$refs.campoQuantidadeInformada.focus())
    },
    focoCampoCustoMedioInformado: function() {
      this.$nextTick(() => this.$refs.campoCustoMedioInformado.focus())
    }

  },
  created() {
    this.filter.codestoquelocal = this.$route.params.codestoquelocal
    this.filter.codmarca = this.$route.params.codmarca
    this.filter.fiscal = this.$route.params.fiscal
    this.filter.data = this.$route.params.data
    this.filter.dataCorte = this.moment().startOf('day').subtract(15, 'days').format('YYYY-MM-DD')
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style>
</style>
