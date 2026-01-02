/**
 * Composable para ícones e utilidades relacionadas a tributos
 */

/**
 * Retorna o ícone apropriado para cada ente federativo
 * @param {string} ente - FEDERAL, ESTADUAL ou MUNICIPAL
 * @returns {string} Nome do ícone do Material Icons
 */
export function getEnteIcon(ente) {
  const icons = {
    FEDERAL: 'account_balance',
    ESTADUAL: 'map',
    MUNICIPAL: 'location_city',
  }
  return icons[ente] || 'gavel'
}

/**
 * Retorna a cor apropriada para cada ente federativo
 * @param {string} ente - FEDERAL, ESTADUAL ou MUNICIPAL
 * @returns {string} Nome da cor do Quasar
 */
export function getEnteColor(ente) {
  const colors = {
    FEDERAL: 'blue',
    ESTADUAL: 'green',
    MUNICIPAL: 'orange',
  }
  return colors[ente] || 'grey'
}

export default function useTributoIcons() {
  return {
    getEnteIcon,
    getEnteColor,
  }
}
