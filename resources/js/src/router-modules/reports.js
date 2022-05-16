/** When your routing table is too long, you can split it into small modules**/
const reportsRoutes = {
  path: '/reports',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'Reports',
  icon: 'BarChart2Icon',
  i18n: 'Reports',
  meta: {
    roles: ['super', 'admin', 'manager'],
  },
  children: [
    // //////////////////Settings///////////////////////////
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
    // {
    //   hidden: false,
    //   path: '/reports/footprints',
    //   name: 'FootPrints',
    //   slug: 'foot-prints',
    //   i18n: 'FootPrints',
    //   component: () => import('@/views/apps/reports/FootPrints'),
    // //   meta: {
    // //     permissions: ['read-inventories'],
    // //   },
    // },
    {
      hidden: false,
      path: '/reports/schedules',
      name: 'Schedules',
      slug: 'schedules',
      i18n: 'Schedules',
      component: () => import('@/views/apps/reports/Schedules'),
    //   meta: {
    //     permissions: ['read-inventories'],
    //   },
    },
    {
      hidden: false,
      component: () => import('@/views/apps/Notifications'),
      path: '/reports/audit-trail',
      name: 'AuditTrail',
      slug: 'audit-trail',
      i18n: 'Audit Trail',
      meta: {
        // roles: ['super'],
      },

    },
  ],

};

export default reportsRoutes;
