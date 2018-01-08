<template>
  <mg-layout back-path="/marca/">

    <!-- Título da Página -->
    <template slot="title">
      Marca: {{ item.marca }}
    </template>

    <!-- Conteúdo Princial (Meio) -->
    <div slot="content">

      <ul class="breadcrumb">
        <li>
          <router-link :to="{ path: '/' }">
            <q-icon name="home"/>
          </router-link>
        </li>
        <li>
          <router-link :to="{ path: '/marca' }">
            <q-icon name="label_outline"/>
          </router-link>
        </li>
        <li>
          <router-link :to="{ path: '/marca/' + this.id }">
            {{ item.marca }}
          </router-link>
        </li>
      </ul>

      <div class="row">
        <div class="col-md-4">
          <q-card>
            <!-- Imagem -->
            <q-card-media v-if="item.codimagem">
              <img :src="item.imagem.url">
            </q-card-media>

            <!-- Titulo -->
            <q-card-title>
              {{ item.marca }}
              <span slot="subtitle">#{{ numeral(item.codmarca).format('00000000') }}</span>
            </q-card-title>
            <q-card-main>
              <dl>
                <dt>Controlada</dt>
                <dd>
                  <span v-if="item.controlada">Sim</span>
                  <span v-else>Não</span>
                </dd>
                <!--
                <dt>Percentual vendas anual</dt>
                <dd>{{ numeral(parseFloat(item.vendaanopercentual)).format('0,0.0000') }}%</dd>
                -->
                <dt>Venda bimentral</dt>
                <dd>{{ numeral(new Intl.NumberFormat().format(item.vendabimestrevalor)).format() }}</dd>
                <dt>Vendas semestral</dt>
                <dd>{{ numeral(new Intl.NumberFormat().format(item.vendasemestrevalor)).format() }}</dd>
                <dt>Vendas anual</dt>
                <dd>{{ numeral(new Intl.NumberFormat().format(item.vendaanovalor)).format() }}</dd>
                <dt>Ativo</dt>
                <dd>
                  <span class="text-red" v-if="item.inativo">Inativo desde {{ moment(item.inativo).format('L') }}</span>
                  <span v-else>Sim</span>
                </dd>
              </dl>
            </q-card-main>
            <q-card-separator />
            <q-card-actions>
              <q-btn color="primary" flat @click.native="activate()" v-if="item.inativo">Ativar</q-btn>
              <q-btn color="red" flat @click.native="inactivate()" v-else>Inativar</q-btn>

              <q-btn color="primary" flat @click="$router.push('/marca/' + item.codmarca + '/foto/')">
                <span v-if="item.codimagem">Alterar Imagem</span>
                <span v-else>Cadastrar Imagem</span>
              </q-btn>
              <q-btn color="red" flat @click="deleteImage()" v-if="item.codimagem">Excluir Imagem</q-btn>
            </q-card-actions>
          </q-card>
        </div>

        <div class="col-md-4" v-if="!item.abcignorar">
          <q-card>
            <q-card-title>
              Curva ABC
              <span slot="subtitle">Dados da curva ABC</span>
              <!-- <q-icon slot="right" name="supervisor_account" /> -->
            </q-card-title>
            <q-card-main>
              <h5><q-rating readonly slot="subtitle" v-model="item.abccategoria" :max="3" /></h5>
              <dl>
                <dt>Percentual de vendas</dt>
                <dd>{{ numeral(parseFloat(item.vendaanopercentual)).format('0,0.0000') }}%</dd>
                <template v-if="item.abcposicao">
                  <dt>Posição</dt>
                  <dd>{{ numeral(item.abcposicao).format('0,0') }}&deg; lugar</dd>
                </template>
                <template v-if="item.dataultimacompra" class="text-grey">
                  <dt>Última compra</dt>
                  <dd>{{ moment(item.dataultimacompra).fromNow() }}</dd>
                </template>
                <template v-if="item.itensabaixominimo > 0">
                  <dt>Itens abaixo do mínimo</dt>
                  <dd>{{ numeral(item.itensabaixominimo).format('0,0') }}</dd>
                </template>
                <template v-if="item.itensacimamaximo > 0">
                  <dt>Itens acima do máximo</dt>
                  <dd>{{ numeral(item.itensacimamaximo).format('0,0') }}</dd>
                </template>
                <dt>Duração do estoque</dt>
                <dd>
                  {{ item.estoqueminimodias }} à
                  {{ item.estoquemaximodias }} Dias
                </dd>
              </dl>
            </q-card-main>
          </q-card>
        </div>

        <div class="col-md-4" v-if="item.site">
          <q-card>
            <q-card-title>
              Site
              <span slot="subtitle">Integração OpenCart</span>
              <!-- <q-icon slot="right" name="supervisor_account" /> -->
            </q-card-title>
            <q-card-main>
              <dl>
                <template v-if="item.codopencart">
                  <dt>Código OpenCart</dt>
                  <dd>{{ item.codopencart }}</dd>
                </template>
                <dt>Descrição</dt>
                <dd>{{ item.descricaosite }}</dd>
              </dl>
            </q-card-main>
          </q-card>
        </div>

        <div class="col-md-4">
          <q-card>
            <q-card-title>
              Produtos abaixo do mínimo
              <span slot="subtitle">Produtos abaixo do estoque mínimo</span>
              <!-- <q-icon slot="right" name="supervisor_account" /> -->
            </q-card-title>
            <q-list separator>
              <template v-for="min in item.produtosAbaixoMinimo">
                  <q-collapsible :label="min.produto">
                    <dl>
                      <dt>Código</dt>
                      <dd>{{ min.codproduto }}</dd>
                      <dt>Código variação</dt>
                      <dd>{{ min.codprodutovariacao }}</dd>
                      <dt>Variação</dt>
                      <dd>{{ min.variação }}</dd>
                      <dt>Produto</dt>
                      <dd>{{ min.produto }}</dd>
                      <dt>Preço</dt>
                      <dd>{{ min.preco }}</dd>
                      <dt>Unidade medida</dt>
                      <dd>{{ min.unidademedida }}</dd>
                      <dt>Referência</dt>
                      <dd>{{ min.referencia }}</dd>
                      <dt>Estoque mínimo</dt>
                      <dd>{{ min.estoqueminimo }}</dd>
                      <dt>Estoque maximo</dt>
                      <dd>{{ min.estoquemaximo }}</dd>
                      <dt>Saldo quantidade</dt>
                      <dd>{{ min.saldoquantidade }}</dd>
                      <dt>Previsão de vendas por dia</dt>
                      <dd>{{ min.vendadiaquantidadeprevisao }}</dd>
                      <dt>Dias</dt>
                      <dd>{{ min.dias }}</dd>
                      <dt>Saldo valor</dt>
                      <dd>{{ min.saldovalor }}</dd>
                      <dt>Data última compra</dt>
                      <dd>{{ min.dataultimacompra }}</dd>
                      <dt>Custo última compra</dt>
                      <dd>{{ min.custoultimacompra }}</dd>
                      <dt>Quantidade última compra</dt>
                      <dd>{{ min.quantidadeultimacompra }}</dd>
                      <dt>Imagem</dt>
                      <dd>{{ min.imagem }}</dd>
                    </dl>
                  </q-collapsible>
              </template>
            </q-list>
          </q-card>
        </div>

        <div class="col-md-4">
          <q-card>
            <q-card-title>
              Produtos acima do máximo
              <span slot="subtitle">Produtos acima do estoque máximo</span>
              <!-- <q-icon slot="right" name="supervisor_account" /> -->
            </q-card-title>
            <q-list separator>
              <template v-for="max in item.produtosAcimaMaximo">
                <q-collapsible :label="max.produto">
                  <dl>
                    <dt>Código</dt>
                    <dd>{{ max.codproduto }}</dd>
                    <dt>Código variação</dt>
                    <dd>{{ max.codprodutovariacao }}</dd>
                    <dt>Variação</dt>
                    <dd>{{ max.variação }}</dd>
                    <dt>Produto</dt>
                    <dd>{{ max.produto }}</dd>
                    <dt>Preço</dt>
                    <dd>{{ max.preco }}</dd>
                    <dt>Unidade medida</dt>
                    <dd>{{ max.unidademedida }}</dd>
                    <dt>Referência</dt>
                    <dd>{{ max.referencia }}</dd>
                    <dt>Estoque mínimo</dt>
                    <dd>{{ max.estoqueminimo }}</dd>
                    <dt>Estoque maximo</dt>
                    <dd>{{ max.estoquemaximo }}</dd>
                    <dt>Saldo quantidade</dt>
                    <dd>{{ max.saldoquantidade }}</dd>
                    <dt>Previsão de vendas por dia</dt>
                    <dd>{{ max.vendadiaquantidadeprevisao }}</dd>
                    <dt>Dias</dt>
                    <dd>{{ max.dias }}</dd>
                    <dt>Saldo valor</dt>
                    <dd>{{ max.saldovalor }}</dd>
                    <dt>Data última compra</dt>
                    <dd>{{ max.dataultimacompra }}</dd>
                    <dt>Custo última compra</dt>
                    <dd>{{ max.custoultimacompra }}</dd>
                    <dt>Quantidade última compra</dt>
                    <dd>{{ max.quantidadeultimacompra }}</dd>
                    <dt>Imagem</dt>
                    <dd>{{ max.imagem }}</dd>
                  </dl>
                </q-collapsible>
              </template>
            </q-list>
          </q-card>
        </div>

      </div>

      <q-fixed-position corner="bottom-right" :offset="[18, 18]">
        <q-fab
          color="primary"
          icon="edit"
          active-icon="edit"
          direction="up"
          class="animate-pop"
        >
          <router-link :to="{ path: '/marca/' + item.codmarca + '/update' }">
            <q-fab-action color="primary" icon="edit">
              <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Editar</q-tooltip>
            </q-fab-action>
          </router-link>
          <q-fab-action color="red" @click.native="destroy()" icon="delete">
            <q-tooltip anchor="center left" self="center right" :offset="[20, 0]">Excluir</q-tooltip>
          </q-fab-action>
        </q-fab>
      </q-fixed-position>

    </div>

    <div slot="footer">
      <mg-autor
        :data="item"
        ></mg-autor>
    </div>

  </mg-layout>
</template>

<script>

import MgLayout from '../../layouts/MgLayout'
import MgAutor from '../../utils/MgAutor'
import {
  QIcon,
  QCard,
  QCardMedia,
  QCardTitle,
  QCardSeparator,
  QCardActions,
  QRating,
  debounce,
  QBtn,
  QFixedPosition,
  QFab,
  QFabAction,
  QTooltip,
  Dialog,
  Toast,
  QCardMain,
  QToggle,
  QCollapsible,
  QList
} from 'quasar'

export default {

  components: {
    MgLayout,
    MgAutor,
    QIcon,
    QCard,
    QCardMedia,
    QCardTitle,
    QCardMain,
    QCardSeparator,
    QCardActions,
    QRating,
    QBtn,
    QFixedPosition,
    QFabAction,
    QFab,
    QTooltip,
    QToggle,
    QCollapsible,
    QList
  },

  data () {
    return {
      item: false,
      id: null
    }
  },
  methods: {
    // carrega registros da api
    loadData: debounce(function () {
      // inicializa variaveis
      var vm = this
      var params = {}
      this.loading = true

      // faz chamada api
      window.axios.get('marca/' + this.id + '/details', { params }).then(response => {
        vm.item = response.data
        // desmarca flag de carregando
        this.loading = false
      })
    }, 500),
    activate: function () {
      let vm = this
      Dialog.create({
        title: 'Ativar',
        message: 'Tem certeza de deseja ativar?',
        buttons: [
          'Cancelar',
          {
            label: 'Ativar',
            handler () {
              window.axios.delete('marca/' + vm.item.codmarca + '/inativo').then(function (request) {
                vm.loadData(vm.item.codmarca)
                Toast.create.positive('Registro ativado')
              }).catch(function (error) {
                console.log(error.response)
              })
            }
          }
        ]
      })
    },
    inactivate: function () {
      let vm = this
      Dialog.create({
        title: 'Inativar',
        message: 'Tem certeza que deseja inativar?',
        buttons: [
          'Cancelar',
          {
            label: 'Inativar',
            handler () {
              window.axios.post('marca/' + vm.item.codmarca + '/inativo').then(function (request) {
                vm.loadData(vm.item.codusuario)
                Toast.create.positive('Registro inativado')
              }).catch(function (error) {
                console.log(error.response)
              })
            }
          }
        ]
      })
    },
    deleteImage: function () {
      let vm = this
      // console.log(vm.item.codimagem)
      Dialog.create({
        title: 'Excluir',
        message: 'Tem certeza de deseja excluir a imagem?',
        buttons: [
          'Cancelar',
          {
            label: 'Excluir',
            handler () {
              window.axios.post('imagem/' + vm.item.codimagem + '/inativo', { codmarca: vm.item.codmarca }).then(function (request) {
                vm.loadData(vm.item.codmarca)
                Toast.create.positive('Imagem excluida')
              }).catch(function (error) {
                console.log(error.response)
              })
            }
          }
        ]
      })
    },
    destroy: function () {
      let vm = this
      Dialog.create({
        title: 'Excluir',
        message: 'Tem certeza que deseja excluir?',
        buttons: [
          'Cancelar',
          {
            label: 'Excluir',
            handler () {
              window.axios.delete('marca/' + vm.item.codmarca).then(function (request) {
                vm.$router.push('/marca')
                Toast.create.positive('Registro excluído')
              }).catch(function (error) {
                console.log(error)
              })
            }
          }
        ]
      })
    }
  },
  created () {
    this.id = this.$route.params.id
    this.loadData()
  }

}
</script>

<style>
</style>
