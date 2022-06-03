/* eslint-disable vue/html-self-closing */
/* eslint-disable vue/html-end-tags */

<template>
  <vx-card v-loading="loader">
    <div id="user-visitData">
      <div class="vx-row">
        <div class="vx-col w-full">
          <div class="flex staffs-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
            <span class="font-medium text-lg">Foot Print</span>
          </div>
          <vs-divider />
        </div>
      </div>
      <el-row :gutter="10">
        <!-- <el-col :lg="8" :md="8" :sm="8" :xs="24">
          <div v-if="!checkRole(['sales_rep'])">
            <span class="demonstration">Select Rep</span>
            <el-select v-model="form.user_id" placeholder="Select Reps" style="width: 100%">
              <el-option
                v-if="salesReps.length > 0"
                label="All"
                value="all" />
              <el-option
                v-for="(rep, index) in salesReps"
                :key="index"
                :label="rep.name"
                :value="rep.id"
              />
            </el-select>
          </div>
        </el-col> -->
        <el-col :lg="8" :md="8" :sm="8" :xs="24">
          <span class="demonstration">Select Date</span>
          <el-date-picker
            v-model="form.date"
            :picker-options="pickerOptions"
            type="date"
            format="yyyy/MM/dd"
            value-format="yyyy-MM-dd"
            placeholder="Pick a day"
            style="width: 100%"/>
        </el-col>
        <el-col :lg="4" :md="4" :sm="4" :xs="24">
          <span class="demonstration">&nbsp;</span>
          <el-button type="primary" style="width: 100%" @click="fetchFootprint()">Fetch</el-button>
        </el-col>
      </el-row>
      <div class="carousel-example">
        <br>
        <!-- <el-tabs>
          <el-tab-pane label="Tabular">
            <v-client-table
              v-model="visits"
              :columns="columns"
              :options="options"
            >
              <template slot="sn" slot-scope="props">
                <span>{{ props.index }}</span>
              </template>
            </v-client-table>
          </el-tab-pane>
          <el-tab-pane v-if="!checkRole(['sales_rep'])" label="Map">

            <gmap-map
              :center="center"
              :zoom="zoom"
              style="width:100%;  height: 650px"
            >
              <gmap-marker
                v-for="(m, index) in markers"
                :key="index"
                :position="m.position"
                :icon="icon"
                @mouseout="showByIndex = null"
                @click="center=m.position; showDetails(m.detail)"
                @mouseover="showByIndex = index;"
              >
                <gmap-info-window
                  :opened="showByIndex === index"
                >
                  <strong>Business Name: </strong> {{ m.detail.customer.business_name }}<br>
                  <strong>Customer Registered Address: </strong> {{ m.detail.customer.address }}<br>
                  <strong>Visited Pinned Address: </strong> {{ m.detail.address }}<br>
                  <strong>Date: </strong> {{ moment(m.detail.created_at).format('DD-MM-YYYY hh:mm a') }}<br>
                </gmap-info-window>
              </gmap-marker>

              <gmap-polyline :path.sync="paths" :options="{ strokeColor:'#000000'}" />
            </gmap-map>
          </el-tab-pane>
        </el-tabs> -->
        <el-row :gutter="10">
          <el-col :lg="16" :md="16" :sm="24" :xs="24">
            <gmap-map
              :center="center"
              :zoom="zoom"
              style="width:100%;  height: 650px"
            >
              <gmap-marker
                v-for="(m, index) in markers"
                :key="index"
                :position="m.position"
                :icon="icon"
                @mouseout="showByIndex = null"
                @click="center=m.position; fetchVisitReports(m.detail)"
                @mouseover="showByIndex = index;"
              >
                <gmap-info-window
                  :opened="showByIndex === index"
                >
                  <strong>Name: </strong> {{ m.detail.name }}<br>
                  <strong>Current Location: </strong> {{ m.detail.location.longitude + ', ' + m.detail.location.latitude }}<br>
                  <strong>Date: </strong> {{ moment(m.detail.location.created_at).format('DD-MM-YYYY hh:mm a') }}<br>
                </gmap-info-window>
              </gmap-marker>

              <gmap-polyline :path.sync="paths" :options="{ strokeColor:'#000000'}" />
            </gmap-map>
          </el-col>
          <el-col :lg="8" :md="8" :sm="24" :xs="24">
            <el-alert :closable="false" type="success">Visits made by {{ selected_rep.name }}</el-alert>
            <br>
            <div v-loading="loadVisits">
              <el-timeline v-if="visits.length > 0">
                <el-timeline-item
                  v-for="(visit, index) in visits"
                  :key="index"
                  :timestamp="moment(visit.created_at).format('DD-MM-YYYY hh:mm a')">
                  <strong>{{ visit.customer.business_name }}</strong>
                </el-timeline-item>
              </el-timeline>
              <div v-else>
                <el-empty description="No visits made" />
              </div>
            </div>
          </el-col>
        </el-row>
      </div>
    </div>
  </vx-card>
</template>

<script>
import moment from 'moment';
import GmapCluster from 'vue2-google-maps/dist/components/cluster';
import GmapPolyline from 'vue2-google-maps/dist/components/polyline';

import checkPermission from '@/utils/permission'; // Permission checking
import checkRole from '@/utils/role'; // Permission checking
import Resource from '@/api/resource';
export default {
  components: {
    GmapCluster, GmapPolyline,
  },
  data() {
    return {
      salesReps: [],
      showByIndex: null,
      pickerOptions: {
        disabledDate(time) {
          return time.getTime() > Date.now();
        },
        shortcuts: [{
          text: 'Today',
          onClick(picker) {
            picker.$emit('pick', new Date());
          },
        }, {
          text: 'Yesterday',
          onClick(picker) {
            const date = new Date();
            date.setTime(date.getTime() - 3600 * 1000 * 24);
            picker.$emit('pick', date);
          },
        }, {
          text: 'A week ago',
          onClick(picker) {
            const date = new Date();
            date.setTime(date.getTime() - 3600 * 1000 * 24 * 7);
            picker.$emit('pick', date);
          },
        }],
      },
      form: {
        date: new Date(),
        // user_id: 'all',
      },
      // /////////////for map /////////////////
      center: { lat: 6.546935900, lng: 3.365565100 }, // default to greenlife office
      zoom: 17,
      icon: '/images/map-image.png',
      // ////////////////////////////////////
      markers: [],
      paths: [],
      loader: false,
      reps_located: [],
      reps_not_located: [],
      selected_rep: '',
      visits: [],
      loadVisits: false,
    };
  },
  created() {
    this.fetchFootprint();
  },
  methods: {
    moment,
    checkPermission,
    checkRole,
    // fetchSalesReps() {
    //   const app = this;
    //   // this.load_table = true;
    //   const salesRepResource = new Resource('users/fetch-sales-reps');
    //   salesRepResource
    //     .list()
    //     .then((response) => {
    //       app.salesReps = response.sales_reps;
    //     })
    //     .catch((error) => {
    //       console.log(error);
    //     });
    // },
    fetchFootprint() {
      this.loader = true;

      const fetchFootprintResource = new Resource('visits/fetch-footprints');
      fetchFootprintResource
        .list(this.form)
        .then((response) => {
          this.reps_located = response.reps_located;
          this.reps_not_located = response.reps_not_located;
          if (this.reps_located.length > 0) {
            this.center = { lat: this.reps_located[0].location.latitude, lng: this.reps_located[0].location.longitude };
          }
          this.addMarker();
          this.loader = false;
        }).catch(() => {
          this.loader = false;
        });
    },
    addMarker() {
      var markers = [];
      var paths = [];
      const icon = '/images/map-marker.png';
      this.reps_located.forEach(rep => {
        const position = {
          lat: rep.location.latitude,
          lng: rep.location.longitude,
        };
        paths.push(position);
        markers.push({ position: position, icon: icon, detail: rep });
      });
      this.paths = paths;
      this.markers = markers;
    },
    // showDetails(rep){
    //   this.selected_rep = rep;
    //   this.$alert('<strong>Name: </strong>' + rep.name + '<br><strong>Current Location: </strong>' + rep.location.longitude + ', ' + rep.location.latitude +
    //   '<br><strong>Date: </strong>' + this.moment(rep.location.created_at).format('DD-MM-YYYY hh:mm a'), rep.name, {
    //     dangerouslyUseHTMLString: true,
    //   });
    // },
    fetchVisitReports(rep) {
      const app = this;
      app.selected_rep = rep;
      app.loadVisits = true;
      const visitsResource = new Resource('visits/fetch-today-visits');
      const param = { rep_id: rep.id, date: app.form.date };
      visitsResource.list(param)
        .then(response => {
          app.visits = response.visits;
          app.loadVisits = false;
        }).catch(() => {
          this.loadVisits = false;
        });
    },
  },
};

</script>

<style lang="scss">
#avatar-col {
  width: 10rem;
}

#page-user-view {
  table {
    td {
      vertical-align: top;
      min-width: 140px;
      padding-bottom: .8rem;
      word-break: break-all;
    }

    &:not(.permissions-table) {
      td {
        @media screen and (max-width:370px) {
          display: block;
        }
      }
    }
  }
}

// #account-info-col-1 {
//   // flex-grow: 1;
//   width: 30rem !important;
//   @media screen and (min-width:1200px) {
//     & {
//       flex-grow: unset !important;
//     }
//   }
// }

@media screen and (min-width:1201px) and (max-width:1211px),
only screen and (min-width:636px) and (max-width:991px) {
  #account-info-col-1 {
    width: calc(100% - 12rem) !important;
  }

  // #account-manage-buttons {
  //   width: 12rem !important;
  //   flex-direction: column;

  //   > button {
  //     margin-right: 0 !important;
  //     margin-bottom: 1rem;
  //   }
  // }

}

</style>
