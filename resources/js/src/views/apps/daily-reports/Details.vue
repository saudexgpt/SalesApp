
<template>
  <div v-loading="loader" id="user-customer">
    <div v-if="report_details">
      <vx-card class="mb-base">
        <div class="vx-row">
          <div class="vx-col lg:w-5/6  w-full">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="Edit2Icon" class="mr-2" />
              <span class="font-medium text-lg">Daily Report Details for {{ moment(report_details.date).format('ll') }}</span>
            </div>
            <vs-divider />
          </div>
          <div class="vx-col lg:w-1/6 w-full">
            <div class="flex items-end px-3">
              <span class="vx-col flex-1">
                <router-link
                  :to="'/daily-reports'"
                >
                  <el-button
                    round
                    class="filter-item"
                    type="danger"
                    icon="el-icon-back"
                  >Go Back
                  </el-button>
                </router-link>
              </span>

            </div>
          </div>
        </div>
        <!-- Avatar -->
        <div class="vx-row">
          <!-- Avatar Col -->
          <div id="avatar-col" class="vx-col">
            <div class="img-container mb-4" @click="showFullPhoto = true">
              <img :src="'/'+report_details.reporter.photo" class="rounded w-full" >
            </div>
          </div>
          <vs-popup
            :active.sync="showFullPhoto"
            title="Reporter Photo">
            <div class="con-exemple-prompt">
              <img :src="'/'+report_details.reporter.photo" class="rounded w-full" >
            </div>
          </vs-popup>

          <!-- Information - Col 1 -->
          <div id="account-info-col-1" class="vx-col flex-1">
            <h4>Reporter's Details</h4>
            <table class="table table-striped">
              <tbody>
                <tr>
                  <td class="font-semibold">Name:</td>
                  <td>{{ report_details.reporter.name }}</td>
                </tr>
                <tr>
                  <td class="font-semibold">Username:</td>
                  <td>{{ report_details.reporter.username }}</td>
                </tr>

              </tbody>
            </table>
          </div>
          <!-- /Information - Col 1 -->

          <!-- Information - Col 2 -->
          <div id="account-info-col-2" class="vx-col flex-1">
            <h4>&nbsp;</h4>
            <table class="table table-striped">
              <tbody>
                <tr>
                  <td class="font-semibold">Phone:</td>
                  <td>{{ report_details.reporter.phone }}</td>
                </tr>
                <tr>
                  <td class="font-semibold">Email:</td>
                  <td>{{ report_details.reporter.email }}</td>
                </tr>
              </tbody>

            </table>
          </div>
        <!-- /Information - Col 2 -->
        <!-- <div id="account-manage-buttons" class="vx-col w-full flex">
            <vs-button :to="{name: 'app-user-edit', params: { userId: $route.params.userId }}" icon-pack="feather" icon="icon-edit" class="mr-4">Edit</vs-button>
            <vs-button type="border" color="danger" icon-pack="feather" icon="icon-trash" @click="confirmDeleteRecord">Delete</vs-button>
          </div> -->

        </div>

      </vx-card>
      <div class="vx-row">
        <div class="vx-col w-full">
          <vx-card class="mb-base">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="ShoppingCartIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Sales Report</span>
            </div>
            <vs-divider />
            <div class="block overflow-x-auto">
              <v-client-table v-model="sales" :columns="sales_columns" :options="sales_options">
                <div slot="child_row" slot-scope="props" style="background: #000">
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Rate</th>
                        <th>Amount</th>
                        <!-- <th>Paid</th>
                    <th>Balance</th>
                    <th>Payment Due Date</th> -->
                      </tr>
                    </thead>
                    <tbody>

                      <tr v-for="(detail, index) in props.row.details" :key="index">

                        <td>{{ detail.product }}</td>
                        <td>{{ detail.quantity }}</td>
                        <td>{{ detail.rate }}</td>
                        <td>{{ currency + Number(detail.amount).toLocaleString() }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div
                  slot="amount_due"
                  slot-scope="props"
                >{{ currency + Number(props.row.amount_due).toLocaleString() }}</div>
                <!-- <div
                  slot="amount_paid"
                  slot-scope="props"
                >{{ currency + Number(props.row.amount_paid).toLocaleString() }}</div>
                <div
                  slot="balance"
                  slot-scope="props"
                >{{ currency + Number(props.row.amount_due - props.row.amount_paid).toLocaleString() }}</div> -->
                <div
                  slot="date"
                  slot-scope="props"
                >{{ moment(props.row.date).format('ll') }}</div>
              </v-client-table>

            </div>
          </vx-card>
        </div>
      </div>
      <div class="vx-row">
        <div class="vx-col w-full">
          <vx-card class="mb-base">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="DollarSignIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Collections Report</span>
            </div>
            <vs-divider />
            <div class="block overflow-x-auto">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Payment Date</th>
                    <!-- <th>Paid</th>
                    <th>Balance</th>
                    <th>Payment Due Date</th> -->
                  </tr>
                </thead>
                <tbody>

                  <tr v-for="(payment, payment_index) in payments" :key="payment_index">

                    <td>{{ payment.customer.business_name }}</td>
                    <td>{{ currency + Number(payment.total).toLocaleString() }}</td>
                    <td>{{ moment(payment.payment_date).format('ll') }}</td>
                  </tr>
                </tbody>
              </table>

            </div>
          </vx-card>
        </div>
      </div>
      <div class="vx-row">
        <div class="vx-col w-full">
          <vx-card class="mb-base">
            <div class="flex items-end px-3">
              <i class="el-icon-sell" size="large" />
              <span class="font-medium text-lg leading-none">Returned Product Report</span>
            </div>
            <vs-divider />
            <div class="block overflow-x-auto">
              <v-client-table v-model="returns" :columns="returns_columns" :options="returns_options">
                <div
                  slot="quantity"
                  slot-scope="props"
                >{{ props.row.quantity + ' ' + props.row.item.package_type }}</div>
                <div
                  slot="amount"
                  slot-scope="props"
                  class="alert alert-success"
                >{{ currency + Number(props.row.amount).toLocaleString() }}</div>
                <div
                  slot="rate"
                  slot-scope="props"
                  class="alert alert-success"
                >{{ currency + Number(props.row.rate).toLocaleString() }}</div>
                <div
                  slot="expiry_date"
                  slot-scope="props"
                >{{ moment(props.row.expiry_date).format('ll') }}</div>
                <div
                  slot="created_at"
                  slot-scope="props"
                >{{ moment(props.row.created_at).format('lll') }}</div>
              </v-client-table>

            </div>
          </vx-card>
        </div>
      </div>
      <div class="vx-row">
        <div v-if="report_details.hospital_reports.length > 0" class="vx-col w-full">
          <vx-card class="mb-base">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="HomeIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Hospital Visit Report</span>
            </div>
            <vs-divider />
            <div v-for="(hospital, hospital_index) in report_details.hospital_reports" :key="hospital_index">

              <!-- Information - Col 1 -->
              <div id="account-info-col-1" class="vx-col flex-1">
                <table class="table table-striped">
                  <tbody>
                    <tr>
                      <td class="font-semibold">Hospital Name:</td>
                      <td>{{ hospital.customer.business_name }}</td>
                    </tr>
                    <tr>
                      <td class="font-semibold">Marketed Products:</td>
                      <td>
                        <span v-for="(product, index) in hospital.marketed_products.split(',')" :key="index">
                          <strong>{{ '(' + parseInt(index + 1) + ') ' + product }}</strong><br>
                        </span>

                      </td>
                    </tr>
                    <tr>
                      <td class="font-semibold">Personnel Contacted:</td>
                      <td>{{ hospital.personnel_contacted }}</td>
                    </tr>
                    <tr>
                      <td class="font-semibold">Follow-up Schedule:</td>
                      <td>{{ moment(hospital.follow_up_schedule).format('lll') }}</td>
                    </tr>
                    <tr>
                      <td class="font-semibold">Feedback/Comment:</td>
                      <td>{{ hospital.market_feedback }}</td>
                    </tr>
                  </tbody>

                </table>
              </div>
            </div>
          </vx-card>
        </div>
        <div class="vx-col w-full">
          <vx-card class="mb-base">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="HomeIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">General Report</span>
            </div>
            <vs-divider />

            <!-- Information - Col 1 -->
            <div id="account-info-col-1" class="vx-col flex-1">
              <table class="table table-striped">
                <tbody>
                  <tr>
                    <td class="font-semibold">Did you work with your manager today?</td>
                    <td>{{ (report_details.work_with_manager_check === '1') ? 'Yes' : 'No' }}</td>
                  </tr>
                  <tr>
                    <td class="font-semibold">No. of hours spent with manager</td>
                    <td>{{ report_details.time_duration_with_manager }}</td>
                  </tr>
                  <tr>
                    <td class="font-semibold">Relationship with manager today</td>
                    <td>{{ report_details.relationship_with_manager }}</td>
                  </tr>
                  <tr>
                    <td class="font-semibold">Feedback/Comment:</td>
                    <td>{{ report_details.market_feedback }}</td>
                  </tr>
                </tbody>

              </table>
            </div>
          </vx-card>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import moment from 'moment';
import 'swiper/dist/css/swiper.min.css';
import { swiper, swiperSlide } from 'vue-awesome-swiper';
import Resource from '@/api/resource';
export default {
  components: { swiper, swiperSlide },
  data() {
    return {
      showFullPhoto: false,
      customer: null,
      // /////////////for map /////////////////
      center: { lat: 3.3792, lng: 6.5244 }, // default to greenlife office
      zoom: 16,
      icon: '/images/map-marker.png',
      // ////////////////////////////////////
      currency: '₦',
      report_details: null,
      sales: [],
      payments: [],
      returns: [],
      sales_columns: [
        'customer.business_name',
        // 'invoice_no',
        'amount_due',
        // 'amount_paid',
        // 'balance',
        'delivery_status',
        'date',
      ],

      sales_options: {
        headings: {
          'customer.business_name': 'Customer',
          // invoice_no: 'Invoice No.',
          amount_due: 'Amount',
          amount_paid: 'Amount Paid',
          delivery_status: 'Delivery Status',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 10,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['created_at'],
        filterable: ['customer.business_name'],
      },
      returns_columns: [
        'customer.business_name',
        'item.name',
        'quantity',
        'rate',
        'amount',
        'batch_no',
        'expiry_date',
        'reason',
        'rep.name', // field staff
        'created_at',
      ],

      returns_options: {
        headings: {
          'customer.business_name': 'Customer',
          'item.name': 'Product',
          'rep.name': 'Field Staff',
          date: 'Date',
          batch_no: 'Batch',
        },
        pagination: {
          dropdown: true,
          chunk: 10,
        },
        perPage: 25,
        filterByColumn: true,
        // texts: {
        //   filter: 'Search:',
        // },
        // editableColumns:['name', 'category.name', 'sku'],
        sortable: ['item.name', 'customer.business_name', 'expiry_date', 'created_at', 'rep.name,'],
        filterable: ['item.name', 'customer.business_name', 'expiry_date', 'created_at', 'rep.name'],
      },
      swiperOption: {
        slidesPerView: 1.1,
        spaceBetween: 5,
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        },
      },
      loader: false,

    };
  },
  mounted() {
    this.fetchDetails();
  },
  methods: {
    moment,
    // loadMap() {
    //   this.$refs.mapRef.$mapPromise.then((map) => {
    //     map.panTo({ lat: this.customer.latitude, lng: this.customer.longitude });
    //   });
    // },
    fetchDetails() {
      const id = this.$route.params && this.$route.params.id;
      const staff = this.$route.params && this.$route.params.staff;
      this.loader = true;
      const param = { report_id: id, staff_id: staff };
      const customerDetailsResource = new Resource('daily-report/details');
      customerDetailsResource
        .list(param)
        .then((response) => {
          this.sales = response.sales_details;
          this.report_details = response.report_details;
          this.payments = response.payments;
          this.returns = response.returns;
          this.loader = false;
        })
        .catch((error) => {
          console.log(error);
          this.loader = false;
        });
    },
    workingDays(){
      const arr = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
      return arr;
    },
    randomColor() {
      const colorCodes = ['success', 'danger', 'warning', 'dark', '#842993', '#fab6b9'];
      const randomColor = colorCodes[Math.floor(Math.random() * colorCodes.length)];
      return randomColor;
      // return this.hexToRgbA(randomColor);
    },
    hexToRgbA(hex){
      var c;
      if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
        c = hex.substring(1).split('');
        if (c.length === 3){
          c = [c[0], c[0], c[1], c[1], c[2], c[2]];
        }
        c = '0x' + c.join('');
        return 'rgba(' + [(c >> 16) & 255, (c >> 8) & 255, c & 255].join(',') + ',0.7)';
      }
      return 'rgba(250, 220, 182, 0.7)';
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
