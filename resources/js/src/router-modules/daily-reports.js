/** When your routing table is too long, you can split it into small modules**/
const reportsRoutes = {
  path: '/daily-reports',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'DailyReports',
  icon: 'BarChart2Icon',
  i18n: 'Daily Reports',
  meta: {
    // roles: ['super', 'admin'],
  },
  children: [
    // //////////////////Settings///////////////////////////

    {
      hidden: false,
      component: () => import('@/views/apps/daily-reports/index'),
      path: '/daily-reports',
      name: 'ViewAll',
      slug: 'view-all',
      i18n: 'View All',
      meta: {
        // permissions: ['create-users', 'read-users', 'update-users', 'delete-users'],
      },
    },
    {
      hidden: false,
      component: () => import('@/views/apps/daily-reports/AddReport'),
      path: '/daily-reports/new',
      name: 'NewReport',
      slug: 'new-report',
      i18n: 'New Report',
      meta: {
        // roles: ['sales_rep'],
      },
    },
    {
      hidden: true,
      path: '/daily-report/details/:id(\\d+)/:staff(\\d+)',
      name: 'DailyReportDetails',
      slug: 'daily-report-details',
      component: () => import('@/views/apps/daily-reports/Details'),
    },
  ],

};

export default reportsRoutes;

