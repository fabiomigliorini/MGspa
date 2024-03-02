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
    },
  }),

  getters: {},

  actions: {

    async getUsuario(codusuario) {
      const ret = await api.get('v1/usuario/' + codusuario + '/detalhes');
      this.detalheUsuarios = ret.data
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
    }

  },
});
