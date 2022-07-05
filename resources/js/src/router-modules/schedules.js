/** When your routing table is too long, you can split it into small modules**/
const adminRoutes = {
  path: '/schedules',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'Schedules',
  icon: 'CalendarIcon',
  i18n: 'Manage Schedules',
  slug: 'schedules',
  redirect: '/schedules/manage',
  meta: {
    permissions: ['manage schedule'],
  },
  children: [
    {
      hidden: true,
      path: '/schedules/manage',
      component: () => import('@/views/apps/schedules/index'),
    },
  ],
};

export default adminRoutes;
