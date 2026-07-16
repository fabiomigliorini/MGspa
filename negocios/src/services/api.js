// Padroniza `src/services/api` em todos os apps (alguns tinham só boot/axios).
// Os componentes compartilhados em @components importam daqui; aqui só
// reexportamos a instância `api` criada no boot.
export { api as default, api } from 'src/boot/axios'
