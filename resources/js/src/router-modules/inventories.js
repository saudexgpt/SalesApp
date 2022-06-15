/** When your routing table is too long, you can split it into small modules**/
const inventoryRoutes = {
  path: '/inventory',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'Inventory',
  icon: 'DatabaseIcon',
  i18n: 'Inventory',
  slug: 'inventory',
  redirect: '/inventory/view',
  meta: {
    // roles: ['sales_rep'],
  },
  children: [
    {
      hidden: false,
      path: '/my-inventory',
      name: 'MyInventory',
      slug: 'my-inventory',
      i18n: 'Manage Inventory',
      component: () => import('@/views/apps/inventories/SalesRepInventory'),
      meta: {
        roles: ['sales_rep'],
      },
    },
    {
      hidden: false,
      component: () => import('@/views/apps/inventories/AddWarehouseSupplies'),
      path: '/inventory/warehouse',
      name: 'AddSupplies',
      slug: 'add-supplied',
      i18n: 'Warehouse Supplies',
      meta: {
        roles: ['sales_rep'],
      },
    },
    {
      hidden: false,
      component: () => import('@/views/apps/inventories/AddInventory'),
      path: '/inventory/add',
      name: 'AddInventory',
      slug: 'add-inventory',
      i18n: 'Add Inventory',
      meta: {
        permissions: ['add-stock'],
      },
    },
    {
      hidden: false,
      path: '/inventory/bulk',
      name: 'BulkUpload',
      slug: 'add-inventory',
      i18n: 'Upload Bulk',
      component: () => import('@/views/apps/inventories/BulkUpload.vue'),
    },
    // {
    //   hidden: true,
    //   path: '/inventory/details/:id(\\d+)',
    //   name: 'InventoryDetails',
    //   slug: 'inventory-details',
    //   i18n: 'Inventory Details',
    //   component: () => import('@/views/apps/customers/Details'),
    // },
  ],
};

export default inventoryRoutes;
