/** When your routing table is too long, you can split it into small modules**/
const customersRoutes = {
  path: '/customers',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'Customers',
  icon: 'UsersIcon',
  i18n: 'Customers',
  slug: 'customers',
  redirect: '/customers/index',
  permissions: ['read-customers'],
  children: [
    {
      hidden: false,
      path: '/customers/index',
      name: 'ViewCustomer',
      slug: 'view-customer',
      i18n: 'View Customer',
      component: () => import('@/views/apps/customers/index.vue'),
    },
    {
      hidden: false,
      path: '/customers/sample',
      name: 'ViewSampleCustomer',
      slug: 'view-sample-customer',
      i18n: 'Sample Customers',
      component: () => import('@/views/apps/customers/Sample'),
    },
    {
      hidden: false,
      component: () => import('@/views/apps/customers/Map'),
      path: '/customers/map',
      name: 'CustomersMap',
      slug: 'customer-map',
      i18n: 'Customers Map',
      meta: {
        // permissions: ['create-users', 'read-users', 'update-users', 'delete-users'],
      },
    },
    {
      hidden: true,
      component: () => import('@/views/apps/customers/MapSampleCustomers'),
      path: '/customers/sample-map',
      name: 'SampleCustomersMap',
      slug: 'sample-customer-map',
      i18n: 'Sample Customers Map',
      meta: {
        // permissions: ['create-users', 'read-users', 'update-users', 'delete-users'],
      },
    },
    {
      hidden: true,
      path: '/customer/details/:id(\\d+)',
      name: 'CustomerDetails',
      slug: 'customer-details',
      i18n: 'Customer Details',
      component: () => import('@/views/apps/customers/Details'),
    },
  ],
};

export default customersRoutes;
