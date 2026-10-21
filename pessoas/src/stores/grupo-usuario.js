import { defineStore } from 'pinia'
import { api } from 'src/services/api'

export const grupoUsuarioStore = defineStore('grupo-usuario', {
  state: () => ({
    grupoUsuarios: [],
    detalheGrupoUsuarios: [],
    filtroGrupoUsuarioPesquisa: {
      grupo: null,
      page: 1,
      inativo: 1,
    },
    dialogGrupoUsuario: false,
    formGrupoUsuario: {},
  }),

  actions: {
    async getGrupoUsuarioDetalhes(codgrupousuario) {
      this.detalheGrupoUsuarios = []
      const ret = await api.get('v1/grupo-usuario/' + codgrupousuario)
      this.detalheGrupoUsuarios = ret.data.data
      return ret
    },

    async todosGruposUsuarios() {
      const ret = await api.get('v1/grupo-usuario/todos', {
        params: this.filtroGrupoUsuarioPesquisa,
      })
      if (this.filtroGrupoUsuarioPesquisa.page == 1) {
        this.grupoUsuarios = ret.data.data
      } else {
        this.grupoUsuarios.push(...ret.data.data)
      }

      return ret
    },

    // async getGrupoUsuarios() {
    //   const ret = await api.get('v1/grupo-usuario');
    //   return ret;
    // },

    // O grupo alterado volta pra listagem também, senão ela fica com o nome/status velho
    atualizarNaLista(grupo) {
      const index = this.grupoUsuarios.findIndex((g) => g.codgrupousuario === grupo.codgrupousuario)
      if (index !== -1) {
        this.grupoUsuarios[index] = grupo
      }
    },

    novoGrupo() {
      this.formGrupoUsuario = { grupousuario: null, observacoes: null }
      this.dialogGrupoUsuario = true
    },

    editarGrupo() {
      this.formGrupoUsuario = {
        codgrupousuario: this.detalheGrupoUsuarios.codgrupousuario,
        grupousuario: this.detalheGrupoUsuarios.grupousuario,
        observacoes: this.detalheGrupoUsuarios.observacoes,
      }
      this.dialogGrupoUsuario = true
    },

    async salvarGrupo() {
      const model = this.formGrupoUsuario
      if (!model.codgrupousuario) {
        const ret = await api.post('v1/grupo-usuario', model)
        this.grupoUsuarios.unshift(ret.data.data)
        return ret
      }
      const ret = await api.put('v1/grupo-usuario/' + model.codgrupousuario + '/alterar', model)
      this.detalheGrupoUsuarios = ret.data.data
      this.atualizarNaLista(ret.data.data)
      return ret
    },

    async getFilial() {
      const ret = await api.get('v1/filial')
      return ret
    },

    async excluir(codgrupousuario) {
      const ret = await api.delete('v1/grupo-usuario/' + codgrupousuario)
      this.grupoUsuarios = this.grupoUsuarios.filter((g) => g.codgrupousuario !== codgrupousuario)
      return ret
    },

    async ativar(codusuario) {
      const ret = await api.delete('v1/grupo-usuario/' + codusuario + '/inativo')
      this.detalheGrupoUsuarios = ret.data.data
      this.atualizarNaLista(ret.data.data)
      return ret
    },

    async inativar(codusuario) {
      const ret = await api.post('v1/grupo-usuario/' + codusuario + '/inativo')
      this.detalheGrupoUsuarios = ret.data.data
      this.atualizarNaLista(ret.data.data)
      return ret
    },
  },
})
