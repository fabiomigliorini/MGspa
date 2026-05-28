import { defineStore } from 'pinia'
import { api } from 'boot/axios'
import { Notify } from 'quasar'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  persist: {
    pick: ['usuario', 'token'],
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
      const params = new URLSearchParams({
        username: usuario,
        password: senha,
        grant_type: 'password',
        client_id: process.env.CLIENT_ID,
        client_secret: process.env.CLIENT_SECRET,
        scope: 'view-user',
      })
      try {
        let { data } = await axios.post(
          process.env.API_AUTH_URL + '/oauth/token',
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
      // cai pra revalidação via /userinfo.
      if (!this.token.refresh_token) {
        await this.carregarUsuario()
        return
      }

      const params = new URLSearchParams({
        refresh_token: this.token.refresh_token,
        grant_type: 'refresh_token',
        client_id: process.env.CLIENT_ID,
        client_secret: process.env.CLIENT_SECRET,
      })
      try {
        let { data } = await axios.post(process.env.API_AUTH_URL + '/oauth/token', params, {
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
        // RFC 7009 — revoga o token passando-o no body + client_credentials
        const params = new URLSearchParams({
          token: this.token.access_token,
          token_type_hint: 'access_token',
          client_id: process.env.CLIENT_ID,
          client_secret: process.env.CLIENT_SECRET,
        })
        await axios.post(process.env.API_AUTH_URL + '/oauth/revoke', params)
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
          // OIDC Core 1.0 §5.3 — /userinfo retorna claims OIDC + custom MGspa
          // num único objeto plano (sem wrapper `data`)
          let { data } = await api.get('/userinfo')
          this.usuario = data
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
        const { data } = await api.get('/userinfo', {
          headers: { Authorization: 'Bearer ' + novoToken.access_token },
        })
        if (data.meta?.expires_at) {
          novoToken.expires_at = data.meta.expires_at
        }
        this.usuario = data
        this.token = novoToken
      } catch (error) {
        console.log(error)
        this.token = novoToken
        this.usuario = {}
      }
    },
  },
})
