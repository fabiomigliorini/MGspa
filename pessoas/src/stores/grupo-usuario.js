import { defineStore } from "pinia";
import { api } from "boot/axios";
import { ref } from "vue";

export const grupoUsuarioStore = defineStore("grupo-usuario", {
  state: () => ({
    grupoUsuarios: [],
    detalheGrupoUsuarios: [],
    filtroGrupoUsuarioPesquisa: {
      grupo: null,
      page: 1,
      inativo: 1
    },
  }),

  actions: {

    async getGrupoUsuarioDetalhes(codgrupousuario) {
      this.detalheGrupoUsuarios = []
      const ret = await api.get('v1/grupo-usuario/' + codgrupousuario);
      this.detalheGrupoUsuarios = ret.data.data
      return ret;
    },

    async todosGruposUsuarios() {
      const ret = await api.get('v1/grupo-usuario/todos', { params: this.filtroGrupoUsuarioPesquisa });
      if (this.filtroGrupoUsuarioPesquisa.page == 1) {
        this.grupoUsuarios = ret.data.data;
      } else {
        this.grupoUsuarios.push(...ret.data.data);
      }

      return ret;
    },


    // async getGrupoUsuarios() {
    //   const ret = await api.get('v1/grupo-usuario');
    //   return ret;
    // },

    async alterarGrupo(model) {
      const ret = await api.put('v1/grupo-usuario/' + model.codgrupousuario + '/alterar', model)
    
      this.detalheGrupoUsuarios = ret.data.data
      return ret;
    },


    async getFilial() {
      const ret = await api.get('v1/filial');
      return ret;
    },

    async excluir(codusuario) {
      const ret = await api.delete('v1/grupo-usuario/' + codusuario)
      return ret;
    },

    async ativar(codusuario) {
      const ret = await api.delete('v1/grupo-usuario/' + codusuario + '/inativo')
    
      this.detalheGrupoUsuarios = ret.data.data
      return ret;

    },

    async inativar(codusuario) {
      const ret = await api.post('v1/grupo-usuario/' + codusuario + '/inativo')
      this.detalheGrupoUsuarios = ret.data.data
      return ret;
    }

  },
});
