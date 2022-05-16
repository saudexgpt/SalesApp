<template>
  <vx-card>
    <vs-tabs>
      <vs-tab label="VISITS REPORT">
        <!-- <customer-visits :customers="customers" /> -->

        <rep-visits :reps="reps" />
      </vs-tab>
      <vs-tab label="REPS FOOT-PRINT">
        <foot-prints :sales-reps="reps"/>
      </vs-tab>
    </vs-tabs>
  </vx-card>
</template>
<script>
import CustomerVisits from './partials/CustomerVisits';
import RepVisits from './partials/RepVisits';
import FootPrints from './Footprints';
import Resource from '@/api/resource';
export default {
  components: { CustomerVisits, RepVisits, FootPrints },
  data() {
    return {
      customers: [],
      reps: [],
    };
  },
  created() {
    // this.fetchCustomers();
    this.fetchSalesReps();
  },
  methods: {
    // fetchCustomers() {
    //   const app = this;
    //   const customerResource = new Resource('customers/all');
    //   customerResource.list()
    //     .then(response => {
    //       app.customers = response.customers;
    //     });
    // },
    fetchSalesReps() {
      const app = this;
      // this.load_table = true;
      const salesRepResource = new Resource('users/fetch-sales-reps');
      salesRepResource
        .list()
        .then((response) => {
          app.reps = response.sales_reps;
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};
</script>
