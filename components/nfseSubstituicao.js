/**
 * Motivos da substituicao da NFS-e (subst/cMotivo da DPS, TASK-279 #5).
 * Espelham o NfseXmlService::MOTIVOS_SUBSTITUICAO do backend.
 */
export const NFSE_MOTIVOS_SUBSTITUICAO = [
  { label: 'Desenquadramento do Simples Nacional', value: 1 },
  { label: 'Enquadramento no Simples Nacional', value: 2 },
  { label: 'Inclusão retroativa de imunidade/isenção', value: 3 },
  { label: 'Exclusão retroativa de imunidade/isenção', value: 4 },
  { label: 'Rejeição pelo tomador ou intermediário', value: 5 },
  { label: 'Outros', value: 99 },
]

// A descricao e' opcional; quando vem, o XSD exige de 15 a 255 caracteres
export const descricaoMotivoSubstituicao = (v) =>
  !v || v.trim().length >= 15 || 'Ao menos 15 caracteres, ou deixe em branco'
