// situacao do dispositivo (tblpdv): ativo = autorizado; nasce inativo (TASK-46)
export const statusDispositivo = (pdv) =>
  pdv?.inativo
    ? { label: 'Inativo', icone: 'pause', cor: 'grey-6' }
    : { label: 'Ativo', icone: 'check', cor: 'green-7' }
