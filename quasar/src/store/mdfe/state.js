export default {
  mdfes: [
  ],
  filtro: {
    codmdfestatus: 1,
  },
  optionsTipoEmitente: [
    {value: 1, label: 'Prestador de Serviço'},
    {value: 2, label: 'Transportador Carga Própria'},
    {value: 3, label: 'Prestador de Serviço - CTe Globalizado'},
  ],
  optionsTipoTransportador: [
    {value: 1, label: 'ETC - Empresa'},
    {value: 2, label: 'TAC - Autônomo'},
    {value: 3, label: 'CTC - Cooperativa'},
  ],
  optionsModal: [
    {value: 1, label: 'Rodoviário'},
    {value: 2, label: 'Aéreo'},
    {value: 3, label: 'Aquaviário'},
    {value: 4, label: 'Ferroviário'},
  ],
  optionsTipoEmissao: [
    {value: 1, label: 'Normal'},
    {value: 2, label: 'Contingência'},
  ],
  colorsStatus: [
    {value: 1, color: 'yellow'},      // EM_DIGITACAO
    {value: 2, color: 'orange'},      // TRANSMITIDA
    {value: 3, color: 'teal'},        // AUTORIZADA
    {value: 4, color: 'red'},         // NAO_AUTORIZADA
    {value: 5, color: 'blue-grey'},   // ENCERRADA
    {value: 9, color: 'red'},         // CANCELADA
  ],
}
