<template>
  <mg-layout>

    <q-btn flat round v-go-back icon="arrow_back" slot="menu" />

    <template slot="title">
      {{ data.usuario }}
    </template>

    <div slot="content">
      <div class="layout-padding">
        <div class="row">
          <div class="col-xs-6 col-sm-4 col-lg-2">
            <div class="card text-center category-link text-primary" @click="$router.push('impressoras')">
              <q-icon name="print" />
              <p class="caption">Impressoras</p>
            </div>
          </div>
          <div class="col-xs-6 col-sm-4 col-lg-2">
            <div class="card text-center category-link text-primary" @click="$router.push('senha')">
              <q-icon name="vpn_key" />
              <p class="caption">Trocar senha</p>
            </div>
          </div>
          <div class="col-xs-6 col-sm-4 col-lg-2">
            <div class="card text-center category-link text-primary" @click="$router.push('foto')">
              <q-icon name="account_box" />
              <p class="caption">Alterar foto</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </mg-layout>
</template>

<script>
import MgLayout from '../../../layouts/MgLayout'
import MgErrosValidacao from '../../utils/MgErrosValidacao'

export default {
  name: 'usuario-profile',
  components: {
    MgLayout,
    MgErrosValidacao
  },
  data () {
    return {
      data: {}
    }
  },
  methods: {
    loadData: function (id) {
      let vm = this
      vm.$axios.get('usuario/' + id).then(function (request) {
        vm.data = request.data
      }).catch(function (error) {
        console.log(error.response)
      })
    }
  },
  created () {
    this.loadData(localStorage.getItem('auth.usuario.codusuario'))
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
