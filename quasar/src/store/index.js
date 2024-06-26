import Vue from 'vue'
import Vuex from 'vuex'

import example from './module-example'
import aplicativos from './aplicativos'
import perfil from './perfil'
import veiculo from './veiculo'
import mdfe from './mdfe'
import filtroMarca from './filtro/marca'
import filtroUsuario from './filtro/usuario'
import filtroFilial from './filtro/filial'
import filtroNotaFiscalTerceiro from './filtro/nota-fiscal-terceiro'
import filtroDfeDistribuicao from './filtro/dfe-distribuicao'
import filtroPix from './filtro/pix'
import estoqueSaldoConferencia from './estoque-saldo-conferencia'

Vue.use(Vuex)

const store = new Vuex.Store({
  modules: {
    example,
    aplicativos,
    perfil,
    veiculo,
    mdfe,
    filtroMarca,
    filtroUsuario,
    filtroFilial,
    filtroNotaFiscalTerceiro,
    filtroDfeDistribuicao,
    filtroPix,
    estoqueSaldoConferencia
  }
})

export default store
