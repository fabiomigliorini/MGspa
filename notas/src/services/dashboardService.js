import api from './api'

export default {
  async getDashboard() {
    const response = await api.get('/v1/nota-fiscal/dashboard')
    return response.data
  },
}
