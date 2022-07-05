/** When your routing table is too long, you can split it into small modules**/
const reportsRoutes = {
  path: '/reports',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'Reports',
  icon: 'BarChart2Icon',
  i18n: 'Reports',
  meta: {
    permissions: ['view reports'],
  },
  children: [
    // //////////////////Settings///////////////////////////
    {
      hidden: false,
      component: () => import('@/views/apps/transactions/Sales'),
      path: '/reports/sales',
      name: 'Sales',
      slug: 'sales',
      i18n: 'Sales',
      meta: {
        // permissions: [],
      },
    },
    {
      hidden: false,
      component: () => import('@/views/apps/transactions/Payments'),
      path: '/reports/collections',
      name: 'Collections',
      slug: 'collections',
      i18n: 'Collections',
      meta: {
        // permissions: [],
      },
    },
    {
      hidden: false,
      component: () => import('@/views/apps/transactions/Debts'),
      path: '/reports/debts',
      name: 'Debts',
      slug: 'debts',
      i18n: 'Debts',
      meta: {
        // permissions: [],
      },
    },
    {
      hidden: false,
      path: '/reports/inventory',
      name: 'InventoryReport',
      slug: 'inventory',
      i18n: 'Inventory',
      component: () => import('@/views/apps/reports/Inventory'),
    //   meta: {
    //     permissions: ['read-inventories'],
    //   },
    },
    {
      hidden: false,
      path: '/reports/visits',
      name: 'Visits',
      slug: 'visits',
      i18n: 'Visits',
      component: () => import('@/views/apps/reports/Visits'),
    //   meta: {
    //     permissions: ['read-inventories'],
    //   },
    },
    {
      hidden: false,
      path: '/reports/footprints',
      name: 'FootPrints',
      slug: 'foot-prints',
      i18n: 'Foot Prints',
      component: () => import('@/views/apps/reports/FootPrints'),
    //   meta: {
    //     permissions: ['read-inventories'],
    //   },
    },
    // {
    //   hidden: false,
    //   path: '/reports/schedules',
    //   name: 'Schedules',
    //   slug: 'schedules',
    //   i18n: 'Schedules',
    //   component: () => import('@/views/apps/reports/Schedules'),
    // //   meta: {
    // //     permissions: ['read-inventories'],
    // //   },
    // },
    {
      hidden: false,
      component: () => import('@/views/apps/Notifications'),
      path: '/reports/audit-trail',
      name: 'AuditTrail',
      slug: 'audit-trail',
      i18n: 'Audit Trail',
      meta: {
        roles: ['super', 'admin'],
      },

    },
  ],

};

export default reportsRoutes;
