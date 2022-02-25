/** When your routing table is too long, you can split it into small modules**/
const settingsRoutes = {
  path: '/settings',
  component: () => import('@/layouts/main/Main.vue'),
  name: 'Settings',
  icon: 'SettingsIcon',
  i18n: 'Admin Settings',
  meta: {
    // roles: ['super', 'admin'],
  },
  children: [
    // //////////////////Settings///////////////////////////

    {
      hidden: true,
      path: '/settings/default-password',
      name: 'DefaultPassword',
      slug: 'default-password',
      component: () => import('@/views/apps/user/ChangeDefaultPassword.vue'),
    },
    {
      hidden: false,
      component: () => import('@/views/apps/user/index.vue'),
      path: '/settings/manage-users',
      name: 'ManageUsers',
      slug: 'manage-users',
      i18n: 'ManageUsers',
      meta: {
        permissions: ['create-users', 'read-users', 'update-users', 'delete-users'],
      },
    },
    {
      hidden: false,
      component: () => import('@/views/apps/teams/index.vue'),
      path: '/settings/manage-teams',
      name: 'ManageTeams',
      slug: 'manage-teams',
      i18n: 'Manage Teams',
      meta: {
        permissions: ['manage-teams'],
      },
    },
    {
      path: 'team/members/:team_id(\\d+)',
      component: () => import('@/views/apps/teams/Members'),
      name: 'TeamMembers',
      meta: { noCache: true, permissions: ['manage-teams'] },
      hidden: true,
    },
    {
      path: 'users/edit/:id(\\d+)',
      component: () => import('@/views/apps/user/UserProfile'),
      name: 'UserProfile',
      // meta: { title: 'userProfile', noCache: true, permissions: ['manage user'] },
      meta: { title: 'userProfile', noCache: true, roles: ['admin'] },
      hidden: true,
    },
    {
      path: '/profile/edit',
      hidden: true,
      component: () =>
        import ('@/views/apps/user/SelfProfile'),
      name: 'SelfProfile',
    },
    // {
    //   hidden: false,
    //   component: () => import('@/views/pages/ComingSoon.vue'),
    //   path: '/settings/general-settings',
    //   name: 'GeneralSettings',
    //   slug: 'general-settings',
    //   i18n: 'GeneralSettings',
    //   meta: {
    //     roles: ['admin'],
    //   },
    // },
    {
      hidden: false,
      component: () => import('@/views/apps/access-control/Roles'),
      path: '/acl/roles',
      name: 'ACL',
      slug: 'roles',
      i18n: 'Access Control',
      meta: {
        roles: ['super', 'admin'],
      },
    },
    // {
    //   hidden: false,
    //   // component: () => import('@/views/pages/ComingSoon.vue'),
    //   path: '/access-control',
    //   name: 'Permissions',
    //   slug: 'external',
    //   i18n: 'User Permissions',
    //   target: '_blank',
    //   meta: {
    //     roles: ['super'],
    //   },

    // },
  ],

};

export default settingsRoutes;
