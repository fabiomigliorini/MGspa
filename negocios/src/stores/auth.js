import { defineStore } from 'pinia'
import { api } from 'boot/axios'
import { Notify } from 'quasar'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  persist: {
    paths: ['usuario', 'token'],
  },

  state: () => ({
    usuario: {},
    token: {},
    dialog: {
      login: false,
    },
  }),

  getters: {},

  actions: {
    async login(usuario, senha) {
      const params = {
        username: usuario,
        password: senha,
        grant_type: 'password',
        client_id: process.env.CLIENT_ID,
        client_secret: process.env.CLIENT_SECRET,
      }
      try {
        let { data } = await axios.post(
          process.env.API_AUTH_URL + '/api/oauth/token/json',
          params,
          { withCredentials: true },
        )
        if (data.access_token) {
          await this.aplicarToken(data)
        }
        return true
      } catch (error) {
        console.log(error)
        let message = ''
        switch (error.code) {
          case 'ERR_NETWORK':
            message = 'Falha ao comunicar com o Servidor!'
            break

          case 'ERR_BAD_RESPONSE':
            message = 'Usuário Incorreto!'
            break

          case 'ERR_BAD_REQUEST':
            message = 'Senha Incorreta!'
            break

          default:
            message = error?.response?.data?.message
            if (!message) {
              message = error?.message
            }
            break
        }
        Notify.create({
          type: 'negative',
          message: message,
          timeout: 3000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        return false
      }
    },

    async renovarToken() {
      // Sem refresh_token (caso típico de login via cookie SSO compartilhado entre apps),
      // o /api/refresh do MGAuth não funciona. Cai pra revalidação via v1/auth/user.
      if (!this.token.refresh_token) {
        await this.carregarUsuario()
        return
      }

      const params = {
        refresh_token: this.token.refresh_token,
        grant_type: 'refresh_token',
        client_id: process.env.CLIENT_ID,
        client_secret: process.env.CLIENT_SECRET,
      }
      try {
        let { data } = await axios.post(process.env.API_AUTH_URL + '/api/refresh', params, {
          withCredentials: true,
        })
        if (data.access_token) {
          await this.aplicarToken(data)
        }
      } catch (error) {
        Notify.create({
          type: 'negative',
          message: 'Falha ao renovar token de autenticação!',
          timeout: 3000,
          actions: [{ icon: 'close', color: 'white' }],
        })
        console.log(error)
      }
    },

    async logout() {
      try {
        await axios.post(
          process.env.API_AUTH_URL + '/api/logout',
          {},
          {
            headers: {
              Authorization: 'Bearer ' + this.token.access_token,
            },
          },
        )
        this.usuario = {}
        this.token = {}
      } catch (error) {
        console.log(error)
      }
    },

    async carregarUsuario() {
      let tokenCookie = document.cookie.split(';').find((c) => c.trim().startsWith('access_token='))
      if (tokenCookie) {
        this.token.access_token = tokenCookie.split('=')[1]
      }

      if (this.token.access_token) {
        try {
          let { data } = await api.get('/api/v1/auth/user')
          this.usuario = data.data
          if (data.meta?.expires_at) {
            this.token.expires_at = data.meta.expires_at
          }
        } catch (error) {
          console.log(error)
          this.usuario = {}
        }
      }
      return this.usuario
    },

    // Aplica token novo (login/refresh) atomicamente: busca o usuário com o token
    // antes de substituir o estado, evitando "pisca" do expires_at no UI.
    async aplicarToken(novoToken) {
      try {
        const { data } = await api.get('/api/v1/auth/user', {
          headers: { Authorization: 'Bearer ' + novoToken.access_token },
        })
        if (data.meta?.expires_at) {
          novoToken.expires_at = data.meta.expires_at
        }
        this.usuario = data.data
        this.token = novoToken
      } catch (error) {
        console.log(error)
        this.token = novoToken
        this.usuario = {}
      }
    },
  },
})
