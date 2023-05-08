/** When your routing table is too long, you can split it into small modules**/
const invoiceRoutes = {
  path: '/invoice-booklet',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'InvoiceBooklet',
  icon: 'BookOpenIcon',
  i18n: 'Invoice Booklet',
  slug: 'invoice-booklet',
  redirect: '/invoice-booklet/manage',
  meta: {
    permissions: ['manage invoice booklet'],
  },
  children: [
    {
      hidden: true,
      path: '/invoice-booklet/manage',
      component: () => import('@/views/apps/invoice-booklet/index'),
    },
  ],
};

export default invoiceRoutes;
