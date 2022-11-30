/** When your routing table is too long, you can split it into small modules**/
const transactionsRoutes = {
  path: '/transactions',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'Transactions',
  icon: 'LinkIcon',
  i18n: 'Transactions',
  redirect: '/transactions/sales',
  meta: {
    // roles: ['super', 'admin'],
  },
  children: [
    // //////////////////transactions///////////////////////////

    {
      hidden: false,
      component: () => import('@/views/apps/daily-reports/AddReport'),
      path: '/daily-reports/new',
      name: 'NewReport',
      slug: 'new-report',
      i18n: 'New Entry',
      meta: {
        // roles: ['sales_rep'],
      },
    },
    {
      hidden: true,
      component: () => import('@/views/apps/transactions/Sales'),
      path: '/transactions/sales',
      name: 'Sales',
      slug: 'sales',
      i18n: 'Sales',
      meta: {
        // permissions: [],
      },
    },
    {
      hidden: true,
      component: () => import('@/views/apps/transactions/Payments'),
      path: '/transactions/collections',
      name: 'Collections',
      slug: 'collections',
      i18n: 'Collections',
      meta: {
        // permissions: [],
      },
    },
    {
      hidden: true,
      component: () => import('@/views/apps/transactions/Debts'),
      path: '/transactions/debts',
      name: 'Debts',
      slug: 'debts',
      i18n: 'Debts',
      meta: {
        // permissions: [],
      },
    },
    // {
    //   hidden: false,
    //   component: () => import('@/views/apps/transactions/Returns'),
    //   path: '/transactions/returns',
    //   name: 'Returns',
    //   slug: 'returns',
    //   i18n: 'Returns',
    //   meta: {
    //     // permissions: [],
    //   },
    // },
    {
      hidden: true,
      component: () => import('@/views/apps/transactions/index'),
      path: '/transactions/index',
      name: 'AllTransactions',
      slug: 'All Transactions',
      i18n: 'View Transactions',
      meta: {
        // permissions: [],
      },
    },
  ],

};

export default transactionsRoutes;
