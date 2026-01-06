import axios from 'axios'

// Cria instância da API
const api = axios.create({
  baseURL: process.env.API_URL,
})

export default api
export { api }
