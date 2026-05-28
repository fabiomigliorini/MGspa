import axios from 'axios'

// Instância dedicada do axios para chamadas à API do app
// (separada do axios "cru" usado em /userinfo e /oauth/revoke).
const api = axios.create({
  baseURL: process.env.API_URL
})

export default api
export { api }
