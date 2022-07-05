/** When your routing table is too long, you can split it into small modules**/
const downloadablesRoutes = {
  path: '/downloadbles',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'Downloadables',
  icon: 'DownloadIcon',
  i18n: 'Downloadables',
  redirect: '/downloadables/index',
  meta: {
    roles: ['super'],
  },
  children: [
    {
      hidden: true,
      component: () => import('@/views/apps/downloadables/index.vue'),
      path: '/downloadables/index',
      name: 'Downloadables',
      slug: 'downloadables',
      i18n: 'Downloadables',
      //   meta: {
      //     permissions: ['create-users', 'read-users', 'update-users', 'delete-users'],
      //   },
    },
  ],

};

export default downloadablesRoutes;
