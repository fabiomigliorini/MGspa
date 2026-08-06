import { register } from 'register-service-worker'

// The ready(), registered(), cached(), updatefound() and updated()
// events passes a ServiceWorkerRegistration instance in their arguments.
// ServiceWorkerRegistration: https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerRegistration

register(process.env.SERVICE_WORKER_FILE, {
  // The registrationOptions object will be passed as the second argument
  // to ServiceWorkerContainer.register()
  // https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerContainer/register#Parameter

  // registrationOptions: { scope: './' },

  ready(/* registration */) {
    console.log('Service worker is active.')
  },

  registered(/* registration */) {
    console.log('Service worker has been registered.')
  },

  cached(/* registration */) {
    console.log('Content has been cached for offline use.')
  },

  updatefound(/* registration */) {
    console.log('New content is downloading.')
  },

  updated(/* registration */) {
    console.log('New content is available; please refresh.')
    // NÃO acende a tarja aqui. O aviso de nova versão é derivado do BUILD_ID do
    // bundle vs /version.json (ver components/pwaAtualizacao.js). Este callback
    // dispara em TODO page load enquanto houver um worker em "waiting" — era
    // exatamente isso que fazia a tarja voltar sem fim. Aqui ele só encurta a
    // latência de detecção, e é idempotente.
    window.dispatchEvent(new Event('pwa-sw-atualizado'))
  },

  offline() {
    console.log('No internet connection found. App is running in offline mode.')
  },

  error(err) {
    console.error('Error during service worker registration')
    console.error('Error during service worker registration:', err)
  },
})
