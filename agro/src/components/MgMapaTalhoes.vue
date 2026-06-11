<script setup>
import { onMounted, onBeforeUnmount, ref, nextTick, watch } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import '@geoman-io/leaflet-geoman-free'
import '@geoman-io/leaflet-geoman-free/dist/leaflet-geoman.css'
import area from '@turf/area'
import { corTalhao } from 'src/utils/coresTalhao'

// Mapa de talhões sobre imagem de satélite (Esri World Imagery, grátis, sem
// chave). Dois modos:
//  - visualizar: desenha N polígonos rotulados (todos os talhões da fazenda)
//  - editar: habilita desenho/edição de UM polígono (Leaflet-Geoman), emitindo
//    geometria (GeoJSON), centro ({lat,lng}) e área (ha, via turf).
const props = defineProps({
  modo: { type: String, default: 'visualizar' }, // 'visualizar' | 'editar'
  talhoes: { type: Array, default: () => [] }, // [{ <idKey>, talhao, geometria }]
  geometria: { type: Object, default: null }, // GeoJSON Polygon (modo editar)
  cor: { type: String, default: '#e53935' }, // cor do polígono em edição
  referencia: { type: Array, default: () => [] }, // outros talhões da MESMA fazenda (contexto no modo editar)
  outras: { type: Array, default: () => [] }, // talhões de OUTRAS fazendas [{ codfazenda, fazenda, geometria }]
  idKey: { type: String, default: 'codtalhao' }, // chave de id emitida no 'select'
  height: { type: String, default: '420px' },
  offsetInferior: { type: Number, default: 0 }, // sobe os controles do rodapé (zoom/desenho) p/ acima do bottom sheet
})

const emit = defineEmits(['update:geometria', 'update:centro', 'update:area', 'select'])

const TILE_SATELITE =
  'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'
const ATTRIB = 'Imagery © Esri, Maxar, Earthstar Geographics'
const BR_CENTRO = [-12.5, -55.0] // Brasil/MT como fallback quando não há geometria

const mapaEl = ref(null)
const online = ref(navigator.onLine)
const termoBusca = ref('')
const buscando = ref(false)
let map = null
let camadaEdicao = null // L.Layer do polígono em edição
let camadasVisualizar = [] // L.Layer dos polígonos no modo visualizar

function setOnline() {
  online.value = navigator.onLine
}

// Busca de local por texto (geocoding gratuito do OpenStreetMap/Nominatim).
async function buscarLocal() {
  const q = termoBusca.value.trim()
  if (!q || !map) return
  buscando.value = true
  try {
    const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(q)}`
    const res = await fetch(url, { headers: { 'Accept-Language': 'pt-BR' } })
    const data = await res.json()
    if (data && data.length) {
      map.setView([Number(data[0].lat), Number(data[0].lon)], 15)
    }
  } catch {
    // busca é best-effort; offline/erro de rede apenas não move o mapa
  } finally {
    buscando.value = false
  }
}

// Centraliza na posição atual do usuário (pede permissão de GPS ao navegador).
function localizar() {
  if (map) map.locate({ setView: true, maxZoom: 16 })
}

// Emite geometria/centro/área a partir da camada em edição (ou limpa tudo).
function emitirDaCamada() {
  if (!camadaEdicao) {
    emit('update:geometria', null)
    emit('update:area', 0)
    return
  }
  const feature = camadaEdicao.toGeoJSON()
  const centro = camadaEdicao.getBounds().getCenter()
  emit('update:geometria', feature.geometry)
  emit('update:centro', { lat: centro.lat, lng: centro.lng })
  emit('update:area', area(feature) / 10000) // m² → ha
}

// Mantém só um polígono: ao criar um novo, remove o anterior.
function registrarCamada(layer) {
  if (camadaEdicao && camadaEdicao !== layer) {
    map.removeLayer(camadaEdicao)
  }
  camadaEdicao = layer
  if (layer.setStyle) {
    layer.setStyle({ color: props.cor, weight: 2, fillColor: props.cor, fillOpacity: 0.35 })
  }
  layer.on('pm:edit', emitirDaCamada)
  emitirDaCamada()
}

function montarVisualizar() {
  // Limpa o que já estava desenhado (redesenho reativo ao mudar `talhoes`).
  camadasVisualizar.forEach((c) => map.removeLayer(c))
  camadasVisualizar = []

  for (const t of props.talhoes) {
    if (!t.geometria) continue
    const camada = L.geoJSON(t.geometria, {
      style: { color: corTalhao(t), weight: 2, fillColor: corTalhao(t), fillOpacity: 0.35 },
    }).addTo(map)
    camada.bindTooltip(t.talhao, {
      permanent: true,
      direction: 'center',
      className: 'bg-transparent',
    })
    camada.on('click', () => emit('select', t[props.idKey]))
    camadasVisualizar.push(camada)
  }
  if (camadasVisualizar.length) {
    const bounds = camadasVisualizar.reduce(
      (b, c) => b.extend(c.getBounds()),
      camadasVisualizar[0].getBounds(),
    )
    map.fitBounds(bounds, { padding: [30, 30] })
  }
}

// Talhões vizinhos como contexto (azul tracejado, não editáveis — pmIgnore).
function desenharReferencia() {
  const feats = props.referencia
    .filter((t) => t.geometria)
    .map((t) => ({
      type: 'Feature',
      properties: { nome: t.talhao, cor: corTalhao(t) },
      geometry: t.geometria,
    }))
  if (!feats.length) return null
  return L.geoJSON(
    { type: 'FeatureCollection', features: feats },
    {
      pmIgnore: true,
      interactive: false,
      style: (f) => ({ color: f.properties.cor, weight: 2, dashArray: '4', fillOpacity: 0.12 }),
      onEachFeature: (f, l) =>
        l.bindTooltip(f.properties.nome, {
          permanent: true,
          direction: 'center',
          className: 'bg-transparent',
        }),
    },
  ).addTo(map)
}

// Polígonos das demais fazendas (contexto cinza, não editáveis), agrupados por
// fazenda para rotular cada uma uma única vez no centro do seu conjunto.
function desenharOutras() {
  const porFazenda = new Map()
  for (const o of props.outras) {
    if (!o.geometria) continue
    if (!porFazenda.has(o.codfazenda)) {
      porFazenda.set(o.codfazenda, { nome: o.fazenda, geometrias: [] })
    }
    porFazenda.get(o.codfazenda).geometrias.push(o.geometria)
  }
  if (!porFazenda.size) return null

  const grupo = L.featureGroup().addTo(map)
  for (const { nome, geometrias } of porFazenda.values()) {
    const camada = L.geoJSON(
      {
        type: 'FeatureCollection',
        features: geometrias.map((g) => ({ type: 'Feature', geometry: g })),
      },
      {
        pmIgnore: true,
        interactive: false,
        style: { color: '#9e9e9e', weight: 2, fillColor: '#9e9e9e', fillOpacity: 0.25 },
      },
    ).addTo(grupo)
    if (nome) {
      camada.bindTooltip(`Fazenda ${nome}`, {
        permanent: true,
        direction: 'center',
        className: 'bg-transparent text-grey-4',
      })
    }
  }
  return grupo
}

function montarEditar() {
  map.pm.addControls({
    position: 'bottomleft',
    drawMarker: false,
    drawCircle: false,
    drawCircleMarker: false,
    drawPolyline: false,
    drawRectangle: false,
    drawText: false,
    drawPolygon: true,
    editMode: true,
    dragMode: false,
    cutPolygon: false,
    rotateMode: false,
    removalMode: true,
  })
  map.pm.setLang('pt_br')

  const outras = desenharOutras()
  const ref = desenharReferencia()

  if (props.geometria) {
    const camada = L.geoJSON(props.geometria, {
      style: { color: props.cor, weight: 2, fillColor: props.cor, fillOpacity: 0.35 },
    })
    // L.geoJSON cria um grupo; pega a camada interna p/ permitir edição direta.
    camada.eachLayer((l) => {
      l.addTo(map)
      registrarCamada(l)
    })
    map.fitBounds(camada.getBounds(), { padding: [30, 30] })
    // Polígono existente já abre com os vértices editáveis (em vez de estático).
    if (camadaEdicao && camadaEdicao.pm) camadaEdicao.pm.enable()
  } else if (ref) {
    // Talhão novo: começa centralizado nos vizinhos da própria fazenda.
    map.fitBounds(ref.getBounds(), { padding: [40, 40] })
  } else if (outras) {
    // Fazenda nova (sem vizinhos): centraliza no conjunto das demais fazendas.
    map.fitBounds(outras.getBounds(), { padding: [60, 60] })
  }

  map.on('pm:create', (e) => registrarCamada(e.layer))
  map.on('pm:remove', () => {
    camadaEdicao = null
    emitirDaCamada()
  })

  // Talhão novo já abre com a ferramenta de desenho de polígono ativa.
  if (!props.geometria) {
    map.pm.enableDraw('Polygon')
  }
}

onMounted(async () => {
  window.addEventListener('online', setOnline)
  window.addEventListener('offline', setOnline)

  // No modo editar o zoom vai pro canto inferior-esquerdo (o topo fica livre
  // pra busca e a base pro bottom sheet de campos).
  map = L.map(mapaEl.value, { center: BR_CENTRO, zoom: 5, zoomControl: props.modo !== 'editar' })
  L.tileLayer(TILE_SATELITE, { attribution: ATTRIB, maxZoom: 19 }).addTo(map)

  if (props.modo === 'editar') {
    L.control.zoom({ position: 'bottomleft' }).addTo(map)
    montarEditar()
    // Levanta os controles do canto inferior-esquerdo (zoom + desenho) p/ acima
    // do bottom sheet de campos, que ocupa a base da tela.
    if (props.offsetInferior) {
      const canto = map.getContainer().querySelector('.leaflet-bottom.leaflet-left')
      if (canto) canto.style.marginBottom = `${props.offsetInferior}px`
    }
  } else {
    montarVisualizar()
  }

  // Talhão novo sem nenhuma fazenda pra usar de referência → centraliza no GPS.
  const temReferencia =
    props.referencia.some((t) => t.geometria) || props.outras.some((o) => o.geometria)
  if (props.modo === 'editar' && !props.geometria && !temReferencia) {
    map.on('locationerror', () => {}) // permissão negada/sem GPS: fica no fallback
    map.locate({ setView: true, maxZoom: 15 })
  }

  // Container dentro de dialog/card pode ter tamanho 0 ao montar.
  await nextTick()
  setTimeout(() => map && map.invalidateSize(), 250)
})

// Redesenha os polígonos quando a lista muda (ex.: cadastrou novo talhão).
watch(
  () => props.talhoes,
  () => {
    if (map && props.modo === 'visualizar') montarVisualizar()
  },
  { deep: true },
)

// Recolore o polígono em edição quando a cor escolhida muda.
watch(
  () => props.cor,
  (c) => {
    if (camadaEdicao && camadaEdicao.setStyle) {
      camadaEdicao.setStyle({ color: c, fillColor: c })
    }
  },
)

onBeforeUnmount(() => {
  window.removeEventListener('online', setOnline)
  window.removeEventListener('offline', setOnline)
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<template>
  <div class="relative-position" :style="{ height, width: '100%' }">
    <div ref="mapaEl" style="height: 100%; width: 100%" />

    <!-- Busca de local + GPS (só no modo editar) -->
    <div
      v-if="modo === 'editar'"
      class="absolute-top row justify-start items-start q-gutter-sm q-pa-sm"
      style="z-index: 1000; pointer-events: none; margin-top: 0px; margin-left: 0px"
    >
      <q-input
        v-model="termoBusca"
        outlined
        bg-color="white"
        placeholder="Buscar cidade, endereço…"
        style="width: min(360px, calc(100% - 160px)); pointer-events: auto"
        @keyup.enter="buscarLocal"
      >
        <template #append>
          <q-btn flat round icon="search" :loading="buscando" @click="buscarLocal" />
          <q-btn
            flat
            round
            color="white"
            text-color="grey-8"
            icon="my_location"
            style="pointer-events: auto"
            @click="localizar"
          >
            <q-tooltip>Minha localização</q-tooltip>
          </q-btn>
        </template>
      </q-input>
    </div>

    <q-banner
      v-if="!online"
      class="absolute-bottom bg-orange-2 text-orange-9 text-caption"
      :style="{ zIndex: 1000, bottom: `${offsetInferior}px` }"
    >
      <template #avatar><q-icon name="cloud_off" color="orange-8" /></template>
      Sem internet — imagem de satélite indisponível.
    </q-banner>
  </div>
</template>
