/**
 * Composable para ícones e utilidades relacionadas a tributos
 */
import { ENTE_CONFIG, getEnteIcon, getEnteColor } from 'src/constants/notaFiscal'

export { getEnteIcon, getEnteColor, ENTE_CONFIG }

export default function useTributoIcons() {
  return {
    getEnteIcon,
    getEnteColor,
    ENTE_CONFIG,
  }
}
