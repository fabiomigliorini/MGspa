<template>
  <mg-layout>

    <q-side-link :to="'/usuario/' + data.codusuario" slot="menu">
      <q-btn flat icon="arrow_back"  />
    </q-side-link>

    <template slot="title">
      {{ data.usuario }}
    </template>

    <div slot="content">
      <div class="layout-padding">
        <div class="row">
          <div class="col-xs-6 col-sm-4 col-lg-2">
            <q-side-link to="impressoras">
              <q-card class="card text-center text-primary">
                <q-icon name="print" />
                <p class="caption">Impressoras</p>
              </q-card>
            </q-side-link>
          </div>
          <div class="col-xs-6 col-sm-4 col-lg-2">
            <q-side-link to="senha">
              <q-card class="card text-center text-primary">
                <q-icon name="vpn_key" />
                <p class="caption">Trocar senha</p>
              </q-card>
            </q-side-link>
          </div>
          <div class="col-xs-6 col-sm-4 col-lg-2">
            <q-side-link to="foto">
              <q-card class="card text-center text-primary">
                <q-icon name="account_box" />
                <p class="caption">Alterar foto</p>
              </q-card>
            </q-side-link>
          </div>
        </div>
<!--
          <div class="col-md-4">
            <q-list multiline link>
              <q-item to="impressoras">
                <q-item-main>
                  <q-item-tile label>Impressoras</q-item-tile>
                  <q-item-tile sublabel>Alterar impressoras</q-item-tile>
                </q-item-main>
                <q-item-side right>
                  <q-item-tile icon="print" color="" />
                </q-item-side>
              </q-item>
              <q-item to="senha">
                <q-item-main>
                  <q-item-tile label>Senha</q-item-tile>
                  <q-item-tile sublabel>Trocar senha do sistema</q-item-tile>
                </q-item-main>
                <q-item-side right>
                  <q-item-tile icon="vpn_key" color="" />
                </q-item-side>
              </q-item>
              <q-item to="foto">
                <q-item-main>
                  <q-item-tile label>Foto</q-item-tile>
                  <q-item-tile sublabel>Cadastrar/Alterar foto</q-item-tile>
                </q-item-main>
                <q-item-side right>
                  <q-item-tile icon="account_box" color="" />
                </q-item-side>
              </q-item>
            </q-list>
          </div>
-->
        </div>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import {
  // Dialog,
  // Toast,
  QBtn,
  QField,
  QInput,
  QSelect,
  QSideLink,
  QUploader,
  QCardMedia,
  QCard,
  QCardMain,
  QCardTitle,
  QList,
  QListHeader,
  QItem,
  QItemSeparator,
  QItemSide,
  QItemMain,
  QItemTile,
  QIcon
} from 'quasar'
import MgLayout from '../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

export default {
  name: 'usuario-profile',
  components: {
    MgLayout,
    MgErrosValidacao,
    QBtn,
    QField,
    QInput,
    QSelect,
    QSideLink,
    QUploader,
    QCardMedia,
    QCard,
    QCardMain,
    QCardTitle,
    QList,
    QListHeader,
    QItem,
    QItemSeparator,
    QItemSide,
    QItemMain,
    QItemTile,
    QIcon
  },
  data () {
    return {
      data: {}
    }
  },
  methods: {
    loadData: function (id) {
      let vm = this
      window.axios.get('usuario/' + id).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  created () {
    this.loadData(localStorage.getItem('auth.codusuario'))
  }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
.card {
	cursor: pointer;
	position: relative;
	padding: 16px;
}

.card .q-icon {
	font-size: 48px;
}

.card p {
	color: rgba(0,0,0,0.87);
	margin: 15px 0 0 0 !important;
}

.card:before {
	content: '';
	position: absolute;
	top: 0;
	right: 0;
	bottom: 0;
	left: 0;
	border-radius: 2px;
	opacity: 0;
	transition: opacity 0.2s;
	background: currentColor;
}

.card:hover:before {
	opacity: 0.4;
}
</style>
