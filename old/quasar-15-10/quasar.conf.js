// Busca Variáveis de Ambiente
const envparser = require('./config/envparser')

// Configuration for your app
module.exports = function (ctx) {
  return {
    // app plugins (/src/plugins)
    plugins: [
      'axios',
      'numeral',
      'moment'
    ],
    css: [
      'app.styl'
    ],
    extras: [
      ctx.theme.mat ? 'roboto-font' : null,
      'material-icons',
      // 'ionicons',
      // 'mdi',
      'fontawesome'
    ],
    supportIE: false,
    /*
    vendor: {
      add: [],
      remove: []
    },
    */
    build: {
      env: envparser(),
      scopeHoisting: true,
      vueRouterMode: 'history',
      // gzip: true,
      // analyze: true,
      // extractCSS: false,
      // useNotifier: false,
      extendWebpack (cfg) {
        cfg.module.rules.push({
          enforce: 'pre',
          test: /\.(js|vue)$/,
          loader: 'eslint-loader',
          exclude: /(node_modules|quasar)/
        })
      }
    },
    devServer: {
      // https: true,
      // port: 8080,
      open: true // opens browser window automatically
    },
    // framework: 'all' --- includes everything; for dev only!
    framework: {
      i18n: 'pt-br',
      components: [
        'QLayout',
        'QLayoutHeader',
        'QLayoutDrawer',
        'QPageContainer',
        'QPage',
        'QToolbar',
        'QToolbarTitle',
        'QBtn',
        'QIcon',
        'QList',
        'QListHeader',
        'QItem',
        'QItemMain',
        'QItemSide',
        'QItemTile',
        'QItemSeparator',

        // Adicionamos
        'QPageSticky',
        'QBtnDropdown',
        'QInfiniteScroll',
        'QLayoutFooter',
        'QFab',
        'QFabAction',
        'QBreadcrumbs',
        'QBreadcrumbsEl',
        'QTooltip',
        'QInput',
        'QCheckbox',
        'QField',
        'QRadio',
        'QOptionGroup',
        'QModal',
        'QCard',
        'QCardMain',
        'QCardTitle',
        'QCardActions',
        'QCardSeparator',
        'QCardMedia',
        'QCollapsible',
        'QToggle',
        'QRange',
        'QScrollArea',
        'QChip',
        'QPopover',
        'QRating',
        'GoBack',
        'QSelect',
        'QDatetime',
        'QTabs',
        'QTab',
        'QTabPane',
        'QRouteTab',
        'QDatetimePicker',
        'QTimeline',
        'QTimelineEntry',
        'QSearch',
        'QStepper',
        'QStep',
        'QStepperNavigation',
        'QProgress',
        'QUploader'
      ],
      directives: [
        'Ripple',
        'TouchSwipe',
        'TouchHold'
      ],
      // Quasar plugins
      plugins: [
        'Notify',
        'Dialog',
        'Loading'
      ]
    },
    // animations: 'all' --- includes all animations
    // animations: [
    // ],
    animations: 'all',
    pwa: {
      // cacheExt: 'js,html,css,ttf,eot,otf,woff,woff2,json,svg,gif,jpg,jpeg,png,wav,ogg,webm,flac,aac,mp4,mp3',
      manifest: {
        name: 'MGspa - MG Papelaria',
        short_name: 'MGspa',
        description: 'Sistema Administrativo MG Papelaria',
        display: 'standalone',
        orientation: 'portrait',
        background_color: '#dedc23',
        theme_color: '#027be3',
        icons: [
          {
            'src': 'statics/icons/icon-128x128.png',
            'sizes': '128x128',
            'type': 'image/png'
          },
          {
            'src': 'statics/icons/icon-192x192.png',
            'sizes': '192x192',
            'type': 'image/png'
          },
          {
            'src': 'statics/icons/icon-256x256.png',
            'sizes': '256x256',
            'type': 'image/png'
          },
          {
            'src': 'statics/icons/icon-384x384.png',
            'sizes': '384x384',
            'type': 'image/png'
          },
          {
            'src': 'statics/icons/icon-512x512.png',
            'sizes': '512x512',
            'type': 'image/png'
          }
        ]
      }
    },
    cordova: {
      // id: 'org.cordova.quasar.app'
    },
    electron: {
      extendWebpack (cfg) {
        // do something with cfg
      },
      packager: {
        // OS X / Mac App Store
        // appBundleId: '',
        // appCategoryType: '',
        // osxSign: '',
        // protocol: 'myapp://path',

        // Window only
        // win32metadata: { ... }
      }
    },

    // leave this here for Quasar CLI
    starterKit: '1.0.2'
  }
}
