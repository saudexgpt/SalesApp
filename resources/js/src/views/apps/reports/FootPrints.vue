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
          <label for="">Select Team</label>
          <el-select v-model="form.team_id" filterable style="width: 100%">
            <el-option
              v-for="(team, index) in teams"
              :key="index"
              :label="team.name"
              :value="team.id"

            />
          </el-select>
        </el-col>
        <el-col :lg="8" :md="8" :sm="8" :xs="24">
          <span class="demonstration">Select Date</span>
          <el-date-picker
            v-model="form.date"
            :picker-options="pickerOptions"
            type="date"
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
            <div v-if="viewType === 'foot_print_list'">

              <gmap-map
                :center="center"
                :zoom="zoom"
                style="width:100%;  height: 600px"
              >
                <gmap-marker
                  v-for="(m, index) in markers"
                  :key="index"
                  :position="m.position"
                  :icon="m.icon"
                  @mouseout="showByIndex = null"
                  @click="center=m.position; fetchVisitReports(m.detail)"
                  @mouseover="showByIndex = index;"
                >
                  <gmap-info-window
                    :opened="showByIndex === index"
                  >
                    <strong>Name: </strong> {{ m.detail.name }}<br>
                    <strong>Last Position (LNG, LAT): </strong> {{ m.position.lng + ', ' + m.position.lat }}<br>
                    <strong>Last Seen Date: </strong> {{ (m.detail.location) ? moment(m.detail.location.created_at).format('lll') : '' }}<br>
                  </gmap-info-window>
                </gmap-marker>

              <!-- <gmap-polyline :path.sync="paths" :options="{ strokeColor:'#000000'}" /> -->
              </gmap-map>
            </div>
            <div v-else>
              <el-button type="danger" round @click="viewType = 'foot_print_list'">Go Back</el-button>
              <gmap-map
                :center="selectedVisit.center"
                :zoom="15"
                style="width:100%;  height: 650px"
              >
                <gmap-marker
                  v-for="(m, index) in selectedVisit.markers"
                  :key="index"
                  :position="m.position"
                  :icon="m.icon"
                  @mouseout="showByIndex = null"
                  @mouseover="showByIndex = index;"
                >
                  <gmap-info-window
                    :opened="showByIndex === index"
                  >
                    <div v-if="m.type === 'customer'">
                      <strong>Name: </strong> {{ m.detail.business_name }}<br>
                      <strong>Location (LNG, LAT): </strong> {{ m.detail.longitude + ', ' + m.detail.latitude }}<br>
                      <strong>Address</strong> {{ m.address }}<br>
                    </div>
                    <div v-else>
                      <strong>Name: </strong> {{ m.detail.visited_by.name }}<br>
                      <strong>Location: </strong> {{ m.detail.rep_longitude + ', ' + m.detail.rep_latitude }}<br>
                      <strong>Address: </strong> {{ m.detail.address }}<br>
                    </div>
                  </gmap-info-window>
                </gmap-marker>

              <!-- <gmap-polyline :path.sync="paths" :options="{ strokeColor:'#000000'}" /> -->
              </gmap-map>
            </div>
          </el-col>
          <el-col :lg="8" :md="8" :sm="24" :xs="24">
            <div v-loading="loadVisits" style="max-height: 600px; overflow: auto">
              <el-alert :closable="false" type="success">Visits made by {{ selected_rep.name }}</el-alert>
              <br>
              <el-timeline v-if="visits.length > 0">
                <el-timeline-item
                  v-for="(visit, index) in visits"
                  :key="index"
                  :timestamp="moment(visit.created_at).format('lll')">
                  <div style="cursor: pointer;" @click="showVisitMapDetails(visit)">
                    <strong>{{ visit.customer.business_name }}</strong><br>
                    <small><strong>Customer's Coordinate: </strong> {{ visit.customer.longitude + ', ' + visit.customer.latitude }}</small><br>
                    <small><strong>Visit Coordinate: </strong>{{ visit.rep_longitude + ', ' + visit.rep_latitude }}</small><br>
                    <small><strong>Proximity: </strong>{{ visit.proximity }}metres</small><br>
                    <small>{{ visit.visit_type }}</small><br>
                  </div>
                  <!-- <small>{{ visit.address }}</small> -->
                </el-timeline-item>
              </el-timeline>
              <div v-else>
                <el-empty description="No visits made" />
              </div>
              <hr>
            </div>
            <div v-if="offline_reps.length > 0" style="max-height: 650px; overflow: auto">
              <el-alert :closable="false" type="error">Offline Reps</el-alert>
              <br>
              <el-timeline >
                <el-timeline-item
                  v-for="(rep, index) in offline_reps"
                  :key="index">
                  <div style="cursor: pointer;">
                    <span><strong>&nbsp;&nbsp;{{ rep.name }}</strong></span>
                    <img class="pull-left" src="/images/offline-rep-image.png" >
                  </div>
                  <!-- <small>{{ visit.address }}</small> -->
                </el-timeline-item>
              </el-timeline>
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
// import GmapPolyline from 'vue2-google-maps/dist/components/polyline';

import checkPermission from '@/utils/permission'; // Permission checking
import checkRole from '@/utils/role'; // Permission checking
import Resource from '@/api/resource';
export default {
  components: {
    GmapCluster,
    // GmapPolyline,
  },
  data() {
    return {
      salesReps: [],
      showByIndex: null,
      viewType: 'foot_print_list',
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
      teams: [],
      form: {
        date: new Date(),
        team_id: '',
      },
      // /////////////for map /////////////////
      center: { lat: 6.546935900, lng: 3.365565100 }, // default to greenlife office
      zoom: 9,
      icon: '',
      // ////////////////////////////////////
      markers: [],
      paths: [],
      loader: false,
      online_reps: [],
      offline_reps: [],
      date: '',
      last_seen_time_gap: '',
      selected_rep: '',
      visits: [],
      loadVisits: false,
      selectedVisit: {
        center: {},
        markers: [],
      },
    };
  },
  created() {
    this.fetchTeams();
  },
  methods: {
    moment,
    checkPermission,
    checkRole,
    fetchTeams() {
      const app = this;
      // this.load_table = true;
      const salesRepResource = new Resource('teams');
      salesRepResource
        .list()
        .then((response) => {
          app.teams = response.teams;
        })
        .catch((error) => {
          console.log(error);
        });
    },
    showVisitMapDetails(visit) {
      const app = this;
      const rep_position = {
        lat: visit.rep_latitude,
        lng: visit.rep_longitude,
      };
      const customer_position = {
        lat: visit.customer.latitude,
        lng: visit.customer.longitude,
      };
      const rep_icon = '/images/map-image.png';
      const customer_icon = {
        url: visit.customer.photo,
        scaledSize: { width: 50, height: 50 },
        labelOrigin: { x: 0, y: 0 },
      };
      app.selectedVisit = {
        center: { customer_position },
        markers: [
          { position: customer_position, icon: customer_icon, type: 'customer', detail: visit.customer },
          { position: rep_position, icon: rep_icon, type: 'rep', detail: visit },
        ],
      };
      app.viewType = 'visit_details';
    },
    // fetchSalesReps() {
    //   const app = this;
    //   // this.load_table = true;
    //   const salesRepResource = new Resource('users/fetch-sales-reps');
    //   salesRepResource
    //     .list()
    //     .then((response) => {
    //       app.salesReps = response.online_reps;
    //     })
    //     .catch((error) => {
    //       console.log(error);
    //     });
    // },
    fetchFootprint() {
      this.loader = true;
      this.visits = [];
      const fetchFootprintResource = new Resource('visits/fetch-footprints');
      fetchFootprintResource
        .list(this.form)
        .then((response) => {
          this.online_reps = response.online_reps;
          this.offline_reps = response.offline_reps;
          this.date = response.date;
          this.last_seen_time_gap = response.last_seen_time_gap;
          //   if (this.online_reps.length > 0) {
          //     this.center = { lat: this.online_reps[0].location.latitude, lng: this.online_reps[0].location.longitude };
          //   }
          this.addMarker();
          this.loader = false;
        }).catch(() => {
          this.loader = false;
        });
    },
    addMarker() {
      const app = this;
      var markers = [];
      var paths = [];
      let icon = '/images/offline-rep-image.png';
      app.online_reps.forEach(rep => {
        const position = {
          lat: rep.location.latitude,
          lng: rep.location.longitude,
        };
        if (rep.presence === 'online') {
          icon = '/images/map-image.png';
        } else if (rep.presence === 'seen'){
          icon = '/images/last-seen-image.png';
        } else {
          icon = '/images/offline-rep-image.png';
        }

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
      const param = { rep_id: rep.id, date: app.date };
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
