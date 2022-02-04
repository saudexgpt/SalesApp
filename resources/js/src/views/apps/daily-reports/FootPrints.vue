/* eslint-disable vue/html-self-closing */
/* eslint-disable vue/html-end-tags */

<template>
  <div v-loading="loader">
    <div id="user-visitData">
      <vx-card>
        <div class="vx-row">
          <div class="vx-col lg:w-3/4 w-full">
            <div class="flex staffs-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="ShoppingBagIcon" class="mr-2" />
              <span class="font-medium text-lg">Foot Print</span>
            </div>
            <vs-divider />
          </div>
          <div class="vx-col lg:w-1/4 w-full">
            <div class="flex staffs-end px-3">
              <span class="pull-right">
                <div v-if="!checkRole(['sales_rep'])">
                  <span class="demonstration">Select Rep</span>
                  <el-select v-model="form.user_id" placeholder="Select Reps">
                    <el-option
                      v-for="(rep, index) in salesReps"
                      :key="index"
                      :label="rep.name"
                      :value="rep.id"
                    />
                  </el-select>
                </div>
                <br>
                <el-date-picker
                  v-model="form.date"
                  :picker-options="pickerOptions"
                  type="date"
                  format="yyyy/MM/dd"
                  value-format="yyyy-MM-dd"
                  placeholder="Pick a day"/>
                <br>
                <el-button type="primary" @click="fetchFootprint()">Fetch</el-button>
              </span>
            </div>
          </div>
        </div>

        <div class="carousel-example">
          <el-tabs>
            <el-tab-pane label="Tabular">
              <v-client-table
                v-model="visits"
                :columns="columns"
                :options="options"
              >
                <template slot="sn" slot-scope="props">
                  <span>{{ props.index }}</span>
                </template>
                <!-- <template slot="action" slot-scope="{row}">
                  <el-tooltip
                    class="item"
                    effect="dark"
                    content="View Report Details"
                    placement="top-start"
                  >
                    <router-link
                      :to="'/daily-report/details/' + row.id + '/' + row.report_by"
                    >
                      <el-button
                        circle
                        type="success"
                        size="small"
                        icon="el-icon-view"
                      />
                    </router-link>
                  </el-tooltip>
                </template> -->
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
                  @click="center=m.position; showDetails(m.detail)"
                />
                <!-- <gmap-cluster :zoom-on-click="true">
                  <gmap-marker
                    v-for="(m, index) in markers"
                    :key="index"
                    :position="m.position"
                    :icon="icon"
                    @click="center=m.position; showDetails(m.detail)"
                  />
                </gmap-cluster> -->

                <gmap-polyline :path.sync="paths" :options="{ strokeColor:'#000000'}" />
              </gmap-map>
            </el-tab-pane>
          </el-tabs>
        </div>
      </vx-card>

    </div>
  </div>
</template>

<script>
import GmapCluster from 'vue2-google-maps/dist/components/cluster';
import GmapPolyline from 'vue2-google-maps/dist/components/polyline';

import checkPermission from '@/utils/permission'; // Permission checking
import checkRole from '@/utils/role'; // Permission checking
import Resource from '@/api/resource';
const fetchFootprintResource = new Resource('visits/fetch-footprints');
export default {
  components: {
    GmapCluster, GmapPolyline,
  },
  props: {
    salesReps: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
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
        date: '',
        user_id: '',
      },
      // /////////////for map /////////////////
      center: { lat: 3.3792, lng: 6.5244 }, // default to greenlife office
      zoom: 17,
      icon: '/images/map-marker.png',
      // ////////////////////////////////////
      markers: [],
      paths: [],
      loader: false,
      visits: [],
      columns: [
        'sn',
        'customer.business_name',
        'rep_latitude',
        'rep_longitude',
        'customer.address',
        'address',
        'visit_date',
      ],

      options: {
        headings: {
          sn: 'S/N',
          'customer.business_name': 'Customer',
          'customer.address': 'Customer Address',
          address: 'Pinned Address',
          rep_latitude: 'Latitude',
          rep_longitude: 'Longitude',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 10,
        // filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['reporter.name', 'date', 'created_at'],
        filterable: ['reporter.name', 'date', 'created_at'],
      },
    };
  },
  methods: {
    checkPermission,
    checkRole,
    fetchFootprint() {
      this.loader = true;
      fetchFootprintResource
        .list(this.form)
        .then((response) => {
          this.visits = response.visits;
          if (this.visits.length > 0) {
            this.center = { lat: this.visits[0].rep_latitude, lng: this.visits[0].rep_longitude };
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
      this.visits.forEach(visit => {
        const position = {
          lat: visit.rep_latitude,
          lng: visit.rep_longitude,
        };
        paths.push(position);
        markers.push({ position: position, icon: icon, detail: visit });
      });
      this.paths = paths;
      this.markers = markers;
    },
    showDetails(visit){
      this.$alert('<strong>Customer Verified Address: </strong>' + visit.customer.address + '<br><strong>Visited Pinned Address: </strong>' + visit.address, visit.customer.business_name, {
        dangerouslyUseHTMLString: true,
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
