<template>
  <div v-if="dashboardData !== null " class="vx-row">

    <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
      <statistics-card-line
        :statistic="dashboardData.customers.length"
        hide-chart
        class="mb-base"
        icon="UsersIcon"
        icon-right
        statistic-title="No. of Customers"
        color="primary" />
    </div>

    <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
      <statistics-card-line
        :statistic="dashboardData.currency + dashboardData.debt.toLocaleString()"
        hide-chart
        class="mb-base"
        icon="CreditCardIcon"
        icon-right
        statistic-title="Total Debt"
        color="warning" />
    </div>

    <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
      <statistics-card-line
        :statistic="dashboardData.currency + dashboardData.overdue.toLocaleString()"
        hide-chart
        class="mb-base"
        icon="CreditCardIcon"
        icon-right
        statistic-title="Overdue Debt"
        color="danger" />
    </div>

    <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
      <statistics-card-line
        :statistic="dashboardData.currency + dashboardData.sales.toLocaleString()"
        hide-chart
        class="mb-base"
        icon="CreditCardIcon"
        icon-right
        statistic-title="All Sales"
        color="dark" />
    </div>
  </div>
</template>

<script>
import StatisticsCardLine from '@/components/statistics-cards/StatisticsCardLine.vue';
export default {
  name: 'DataAnalysis',
  components: { StatisticsCardLine },
  props: {
    dashboardData: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
    };
  },
};
</script>
