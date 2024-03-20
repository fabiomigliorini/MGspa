import { defineStore } from "pinia";
import { api } from "boot/axios";
import { ref } from "vue";

export const usuarioStore = defineStore("usuario", {
  state: () => ({
    usuarios: [],
    detalheUsuarios: [],
    filtroUsuarioPesquisa: {
      usuario: null,
      page: 1,
      inativo: 1
    },
  }),

  getters: {},

  actions: {

    async getUsuario(codusuario) {
      const ret = await api.get('v1/usuario/' + codusuario + '/detalhes');
      this.detalheUsuarios = ret.data.data
      return ret;
    },

    async todosUsuarios() {
      const ret = await api.get('v1/usuario/todos', { params: this.filtroUsuarioPesquisa });
      if (this.filtroUsuarioPesquisa.page == 1) {
        this.usuarios = ret.data.data;
      } else {
        this.usuarios.push(...ret.data.data);
      }

      return ret;
    },


    async getGrupoUsuarios() {
      const ret = await api.get('v1/grupo-usuario');
      return ret;
    },

    async getFilial() {
      const ret = await api.get('v1/filial');
      return ret;
    },

    async excluirUsuario(codusuario) {
      const ret = await api.delete('v1/usuario/' + codusuario)
      return ret;
    },

    async ativar(codusuario) {
      const ret = await api.delete('v1/usuario/' + codusuario + '/inativo')
      this.detalheUsuarios = ret.data.data
      return ret;

    },

    async inativar(codusuario) {
      const ret = await api.post('v1/usuario/' + codusuario + '/inativo')
      this.detalheUsuarios = ret.data.data
      return ret;
    },

    async usuarioAlterarPerfil(model) {
      const ret = await api.put('v1/usuario/' + model.codusuario + '/alterar', model)
      return ret;
    },

    async putUsuario(model) {
      const ret = await api.put('v1/usuario/' + model.codusuario + '/grupos-usuarios', model)
      return ret;
    },

    async postUsuario(model) {
      const ret = await api.post('v1/usuario/criar', model)
      return ret;
    },

    async resetarSenha(codusuario) {
      const ret = await api.get('v1/usuario/' + codusuario +'/reset-senha')
      return ret;
    }

  },
});
