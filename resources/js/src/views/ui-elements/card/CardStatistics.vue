<!-- =========================================================================================
    File Name: CardStatistics.vue
    Description: Statistics Card
    ----------------------------------------------------------------------------------------
    Item Name: Vuexy - Vuejs, HTML & Laravel Admin Dashboard Template
      Author: Pixinvent
    Author URL: http://www.themeforest.net/user/pixinvent
========================================================================================== -->

<template>
  <div>

    <!-- ROW 1-->
    <div class="vx-row">
      <div class="vx-col w-1/2 md:w-1/3 xl:w-1/6">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="EyeIcon"
          statistic="36.9k"
          statistic-title="Views" />
      </div>

      <div class="vx-col w-1/2 md:w-1/3 xl:w-1/6">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="MessageSquareIcon"
          statistic-title="Comments"
          statistic="12k"
          color="success" />
      </div>

      <div class="vx-col w-1/2 md:w-1/3 xl:w-1/6">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="ShoppingBagIcon"
          statistic="978"
          statistic-title="Orders"
          color="warning" />
      </div>

      <div class="vx-col w-1/2 md:w-1/3 xl:w-1/6">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="HeartIcon"
          statistic="26.8k"
          statistic-title="Favorited"
          color="danger" />
      </div>

      <div class="vx-col w-1/2 md:w-1/3 xl:w-1/6">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="SmileIcon"
          statistic="689"
          statistic-title="Reviews"
          color="success" />
      </div>

      <div class="vx-col w-1/2 md:w-1/3 xl:w-1/6">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="TruckIcon"
          statistic="2"
          statistic-title="Returns"
          color="warning" />
      </div>
    </div>

    <!-- ROW 2 -->
    <div class="vx-row">

      <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="CpuIcon"
          icon-right
          statistic="86%"
          statistic-title="CPU Usage" />
      </div>

      <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="ServerIcon"
          icon-right
          statistic="1.2gb"
          statistic-title="Memory Usage"
          color="success" />
      </div>

      <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="ActivityIcon"
          icon-right
          statistic="0.1%"
          statistic-title="Downtime Ratio"
          color="danger" />
      </div>

      <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
        <statistics-card-line
          hide-chart
          class="mb-base"
          icon="AlertOctagonIcon"
          icon-right
          statistic="13"
          statistic-title="Issues Found"
          color="warning" />
      </div>
    </div>

    <!-- ROW 3 -->
    <div class="vx-row">
      <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
        <statistics-card-line
          v-if="subscribersGained.analyticsData"
          :statistic="subscribersGained.analyticsData.subscribers | k_formatter"
          :chart-data="subscribersGained.series"
          class="mb-base"
          icon="UsersIcon"
          statistic-title="Subscribers Gained"
          type="area" />
      </div>

      <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
        <statistics-card-line
          v-if="revenueGenerated.analyticsData"
          :statistic="revenueGenerated.analyticsData.revenue | k_formatter"
          :chart-data="revenueGenerated.series"
          class="mb-base"
          icon="DollarSignIcon"
          statistic-title="Revenue Generated"
          color="success"
          type="area" />
      </div>

      <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
        <statistics-card-line
          v-if="quarterlySales.analyticsData"
          :statistic="quarterlySales.analyticsData.sales"
          :chart-data="quarterlySales.series"
          class="mb-base"
          icon="ShoppingCartIcon"
          statistic-title="Quarterly Sales"
          color="danger"
          type="area" />
      </div>
      <div class="vx-col w-full sm:w-1/2 md:w-1/2 lg:w-1/4 xl:w-1/4">
        <statistics-card-line
          v-if="ordersRecevied.analyticsData"
          :statistic="ordersRecevied.analyticsData.orders | k_formatter"
          :chart-data="ordersRecevied.series"
          class="mb-base"
          icon="ShoppingBagIcon"
          statistic-title="Orders Received"
          color="warning"
          type="area" />
      </div>
    </div>

    <!-- ROW 4 -->
    <div class="vx-row">

      <div class="vx-col w-full md:w-1/3 lg:w-1/3 xl:w-1/3">
        <statistics-card-line
          :chart-data="siteTraffic.series"
          class="md:mb-0 mb-base"
          icon="MonitorIcon"
          icon-right
          statistic="78.9k"
          statistic-title="Site Traffic" />
      </div>

      <div class="vx-col w-full md:w-1/3 lg:w-1/3 xl:w-1/3">
        <statistics-card-line
          :chart-data="activeUsers.series"
          class="md:mb-0 mb-base"
          icon="UserCheckIcon"
          icon-right
          statistic="659.8k"
          statistic-title="Active Users"
          color="success" />
      </div>

      <div class="vx-col w-full md:w-1/3 lg:w-1/3 xl:w-1/3">
        <statistics-card-line
          :chart-data="newsletter.series"
          icon="MailIcon"
          icon-right
          statistic="28.7k"
          statistic-title="Newsletter"
          color="warning" />
      </div>
    </div>

  </div>
</template>

<script>
import StatisticsCardLine from '@/components/statistics-cards/StatisticsCardLine.vue';

export default{
  components: {
    StatisticsCardLine,
  },
  data() {
    return {
      // Area charts
      subscribersGained: {},
      revenueGenerated: {},
      quarterlySales: {},
      ordersRecevied: {},

      // Line Charts
      siteTraffic: {},
      activeUsers: {},
      newsletter: {},
    };
  },
  created() {
    // Subscribers gained
    this.$http.get('/api/card/card-statistics/subscribers')
      .then((response) => {
        this.subscribersGained = response.data;
      })
      .catch((error) => {
        console.log(error);
      });

    // Revenue Generated
    this.$http.get('/api/card/card-statistics/revenue')
      .then((response) => {
        this.revenueGenerated = response.data;
      })
      .catch((error) => {
        console.log(error);
      });

    // Sales
    this.$http.get('/api/card/card-statistics/sales')
      .then((response) => {
        this.quarterlySales = response.data;
      })
      .catch((error) => {
        console.log(error);
      });

    // Orders
    this.$http.get('/api/card/card-statistics/orders')
      .then((response) => {
        this.ordersRecevied = response.data;
      })
      .catch((error) => {
        console.log(error);
      });

    // Site Traffic gained
    this.$http.get('/api/card/card-statistics/site-traffic')
      .then((response) => {
        this.siteTraffic = response.data;
      })
      .catch((error) => {
        console.log(error);
      });

    // Active Users
    this.$http.get('/api/card/card-statistics/active-users')
      .then((response) => {
        this.activeUsers = response.data;
      })
      .catch((error) => {
        console.log(error);
      });

    // Site Traffic gained
    this.$http.get('/api/card/card-statistics/newsletter')
      .then((response) => {
        this.newsletter = response.data;
      })
      .catch((error) => {
        console.log(error);
      });
  },
};
</script>
