<template>
  <div v-if="currentRole !== ''" class="dashboard-container">
    <component :is="currentRole" />
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import superAdminDashboard from './super';
import adminDashboard from './admin';
import salesRepDashboard from './sales-rep';

export default {
  name: 'Dashboard',
  components: { superAdminDashboard, adminDashboard, salesRepDashboard },
  data() {
    return {
      currentRole: '',
    };
  },
  computed: {
    ...mapGetters([
      'roles',
    ]),
  },
  created() {
    if (this.roles.includes('super')) {
      this.currentRole = 'superAdminDashboard';
    } else if (this.roles.includes('admin') || this.roles.includes('auditor')) {
      this.currentRole = 'adminDashboard';
    } else if (this.roles.includes('sales_rep')){
      this.currentRole = 'salesRepDashboard';
    }
  },
};
</script>
