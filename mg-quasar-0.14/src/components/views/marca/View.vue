<template>
  <mg-layout back-path="/marca">

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

      <q-card v-if="item.inativo">
        <q-card-main>
          <span class="text-red">
            Inativo desde {{ moment(item.inativo).format('L') }}
          </span>
        </q-card-main>
      </q-card>

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
                <!-- <dt>Percentual vendas anual</dt>
                <dd>{{ numeral(parseFloat(item.vendaanopercentual)).format('0,0.0000') }}%</dd> -->
                <dt>Venda bimentral</dt>
                <dd>{{ item.vendabimestrevalor }}</dd>
                <dt>Vendas semestral</dt>
                <dd>{{ item.vendasemestrevalor }}</dd>
                <dt>Vendas anual</dt>
                <dd>{{ item.vendaanovalor }}</dd>
                <dt></dt>
                <dd>
                  <span v-if="item.inativo">Ativar</span>
                  <span v-else>Inativar</span>
                  <q-toggle v-model="ativo" @blur="teste()" class="right"/>
                </dd>
              </dl>
            </q-card-main>
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
                <dt>Código OpenCart</dt>
                <dd>{{ item.codopencart }}</dd>
                <dt>Descrição</dt>
                <dd>{{ item.descricaosite }}</dd>
              </dl>
            </q-card-main>
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
  QToggle
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
    QToggle
  },

  data () {
    return {
      item: {},
      ativo: true,
      id: null
    }
  },

  methods: {
    teste: function () {
      if (this.ativo) {
        this.inactivate()
      }
      else {
        this.activate()
      }
    },
    // carrega registros da api
    loadData: debounce(function () {
      // inicializa variaveis
      var vm = this
      var params = {}
      this.loading = true

      // faz chamada api
      window.axios.get('marca/' + this.id + '/details', { params }).then(response => {
        vm.item = response.data
        if (vm.item.inativo === null) {
          vm.ativo = true
        }
        else {
          vm.ativo = false
        }
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

  // na criacao, busca filtro do Vuex
  created () {
    this.id = this.$route.params.id
    this.loadData()
  }

}
</script>

<style>
.q-card-media {
  padding: 0 15px
}
</style>
