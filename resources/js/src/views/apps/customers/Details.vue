
<template>
  <div v-loading="loader" id="user-customer">
    <div v-if="customer">
      <vx-card class="mb-base">
        <div class="vx-row">
          <div class="vx-col lg:w-3/5  w-3/5">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="UserIcon" class="mr-2" />
              <span class="font-medium text-lg">Customer Account</span>
            </div>
            <vs-divider />
          </div>
          <div class="vx-col lg:w-2/5 w-2/5 pull-right">
            <div class="flex items-end px-3">
              <span class="vx-col flex-1">
                <router-link
                  :to="'/customers/index'"
                >
                  <el-button
                    round
                    class="filter-item"
                    size="small"
                    type="danger"
                    icon="el-icon-back"
                  />
                </router-link>
                <router-link
                  :to="'/report/customer-statement/' + $route.params.id"
                >
                  <el-button
                    round
                    type="success"
                    size="small"
                    icon="el-icon-view"
                  >View Statement
                  </el-button>
                </router-link>
                <el-button
                  v-if="checkPermission(['verify-customers'])"
                  round
                  type="primary"
                  size="small"
                  icon="el-icon-thumb"
                  @click="verifyCustomer(customer.id)"
                >Verify
                </el-button>
              </span>

            </div>
          </div>
        </div>
        <!-- Avatar -->
        <div class="vx-row">

          <!-- Avatar Col -->
          <div id="avatar-col" class="vx-col">
            <div class="img-container mb-4" @click="showFullPhoto = true">
              <img :src="customer.photo" class="rounded w-full" >
            </div>
          </div>
          <vs-popup
            :active.sync="showFullPhoto"
            title="Customer Photo">
            <div class="con-exemple-prompt">
              <img :src="customer.photo" class="rounded w-full" >
            </div>
          </vs-popup>

          <!-- Information - Col 1 -->
          <div id="account-info-col-1" class="vx-col flex-1">
            <table class="table table-striped">
              <tbody>
                <tr>
                  <td class="font-semibold">Name:</td>
                  <td>{{ customer.business_name }}</td>
                </tr>
                <tr>
                  <td class="font-semibold">Customer Type:</td>
                  <td>{{ (customer.customer_type) ? customer.customer_type.name : '' }}</td>
                </tr>
                <tr>
                  <td class="font-semibold">Address:</td>
                  <td>{{ customer.address }}</td>
                </tr>

              </tbody>
            </table>
          </div>
          <!-- /Information - Col 1 -->

          <!-- Information - Col 2 -->
          <div id="account-info-col-2" class="vx-col flex-1">
            <table class="table table-striped">
              <tbody>
                <tr>
                  <td class="font-semibold">Street:</td>
                  <td>{{ customer.street }}</td>
                </tr>
                <tr>
                  <td class="font-semibold">Area:</td>
                  <td>{{ customer.area }}</td>
                </tr>
                <tr>
                  <td class="font-semibold">LGA:</td>
                  <td>{{ (customer.lga) ? customer.lga.name : '' }}</td>
                </tr>
                <tr>
                  <td class="font-semibold">State:</td>
                  <td>{{ (customer.state) ? customer.state.name : '' }}</td>
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
        <div class="vx-col lg:w-1/2 w-full">
          <vx-card class="mb-base">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="ThumbsUpIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Verifications</span>
            </div>
            <vs-divider />
            <div class="block overflow-x-auto">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Verified By</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>

                  <tr v-for="(verification, index) in customer.verifications" :key="index">
                    <td class="px-3 py-2">{{ verification.verifier.name }}</td>
                    <td class="px-3 py-2">{{ moment(verification.created_at).format('lll') }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </vx-card>
          <vx-card class="mb-base">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="CalendarIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Schedule</span>
            </div>
            <vs-divider />
            <div class="carousel-example">
              <swiper :key="$vs.rtl" :options="swiperOption" :dir="$vs.rtl ? 'rtl' : 'ltr'">
                <swiper-slide v-for="(day, day_index) in workingDays()" :key="day_index">
                  <div class="mb-base">
                    <h3 align="center">{{ day }}</h3>
                    <div v-if="!schedules[day]" align="center">
                      <img :src="'/images/calender.png'" style="width: 50% !important;" alt="Not Found" >
                      <h4>No schedule for {{ day }}</h4>
                    </div>
                    <div v-else>
                      <!-- <vs-list>
                      <vs-list-item v-for="(each_entry, index) in schedules[day]" :key="index" :title="moment(each_entry.schedule_date + ' ' + each_entry.schedule_time).format('LT')" :subtitle="each_entry.note"/>
                    </vs-list> -->
                      <vs-alert v-for="(each_entry, index) in schedules[day]" :key="index" :title="moment(each_entry.schedule_date + ' ' + each_entry.schedule_time).format('LT')" :color="randomColor()" active="true">
                        {{ each_entry.note }}
                      </vs-alert>
                    </div>
                  </div>
                </swiper-slide>
                <div slot="pagination" class="swiper-pagination"/>
              </swiper>
            </div>
          </vx-card>
        </div>

        <div v-loading="loader" class="vx-col lg:w-1/2 w-full">
          <vx-card class="mb-base">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Contacts Information</span>
              <span class="pull-right">
                <el-button v-if="checkPermission(['create-customers'])" circle size="small" type="warning" icon="el-icon-plus" @click="dialogVisible = true" />
              </span>
            </div>
            <vs-divider />
            <div v-loading="removing_contact" class="block overflow-x-auto">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Phone No.</th>
                    <th>Alt No.</th>
                    <th>Role</th>
                    <th />
                  </tr>
                </thead>
                <tbody>

                  <tr v-for="(contact, index) in customer.customer_contacts" :key="index">
                    <td class="px-3 py-2">{{ contact.name }}</td>
                    <td class="px-3 py-2">{{ contact.phone1 }}</td>
                    <td class="px-3 py-2">{{ contact.phone2 }}</td>
                    <td class="px-3 py-2">{{ contact.role }}</td>
                    <td>
                      <el-button
                        circle
                        type="danger"
                        size="small"
                        icon="el-icon-delete"
                        @click="removeContact(contact, index)"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </vx-card>
          <vx-card class="mb-base">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="MapPinIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Map Location</span>
            </div>
            <vs-divider />
            <div class="carousel-example">
              <gmap-map
                :center="center"
                :zoom="zoom"
                style="width:100%;  height: 500px;"
              >
                <gmap-marker
                  :position="center"
                  :icon="icon"
                  @click="center=center"
                />
              </gmap-map>
            </div>
          </vx-card>
        </div>
      </div>

      <vx-card class="mb-base">

        <div class="vx-row">
          <div class="vx-col w-full">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="LinkIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Recent Transactions</span>
            </div>
            <vs-divider />
          </div>
        </div>

        <v-client-table
          v-model="customer.transactions"
          :columns="columns"
          :options="options"
        >
          <div slot="child_row" slot-scope="props">
            <aside>
              <div>Transaction Details For Invoice No.: {{ props.row.invoice_no }}</div>

              <v-client-table
                v-model="props.row.details"
                :columns="['product', 'quantity', 'rate', 'amount', 'supply_status']"
              >
                <div slot="quantity" slot-scope="{row}">
                  <span>{{ row.quantity }} {{ row.packaging }}</span>
                </div>
                <template slot="rate" slot-scope="{row}">
                  <span>{{ currency + Number(row.rate).toLocaleString() }}</span>
                </template>
                <template slot="amount" slot-scope="{row}">
                  <span>{{ currency + Number(row.amount).toLocaleString() }}</span>
                </template>
              </v-client-table>
            </aside>
          </div>
          <template slot="created_at" slot-scope="props">
            <span>{{ moment(props.row.created_at).format('ll') }}</span>
          </template>
          <template slot="amount_due" slot-scope="props">
            <span>{{ currency + Number(props.row.amount_due).toLocaleString() }}</span>
          </template>
          <template slot="amount_paid" slot-scope="props">
            <span>{{ currency + Number(props.row.amount_paid).toLocaleString() }}</span>
          </template>
        </v-client-table>
        <br>
      </vx-card>
      <vx-card class="mb-base">

        <div class="vx-row">
          <div class="vx-col w-full">
            <div class="flex items-end px-3">
              <feather-icon svg-classes="w-6 h-6" icon="PhoneOutgoingIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Recent Visits</span>
            </div>
            <vs-divider />
          </div>
        </div>

        <v-client-table
          v-model="customer.visits"
          :columns="['visit_date', 'visited_by', 'details']"
        >
          <!-- <div slot="child_row" slot-scope="props">
            <aside>
              <div>Transaction Details For Invoice No.: {{ props.row.invoice_no }}</div>

              <v-client-table
                v-model="props.row.details"
                :columns="['product', 'quantity', 'rate', 'amount', 'supply_status']"
              >
                <div slot="quantity" slot-scope="{row}">
                  <span>{{ row.quantity }} {{ row.packaging }}</span>
                </div>
                <template slot="rate" slot-scope="{row}">
                  <span>{{ currency + Number(row.rate).toLocaleString() }}</span>
                </template>
                <template slot="amount" slot-scope="{row}">
                  <span>{{ currency + Number(row.amount).toLocaleString() }}</span>
                </template>
              </v-client-table>
            </aside>
          </div> -->
          <template slot="visited_by" slot-scope="props">
            <span>{{ props.row.visited_by.name }}</span>
          </template>
          <template slot="details" slot-scope="props">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>S/N</th>
                  <th>Visit Type</th>
                  <th>Purpose</th>
                  <th>Description</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(detail, index) in props.row.details" :key="index">
                  <td>{{ index + 1 }}</td>
                  <td>{{ detail.visit_type }}</td>
                  <td>{{ detail.purpose }}</td>
                  <td>{{ detail.description }}</td>
                </tr>
              </tbody>
            </table>
          </template>
        </v-client-table>
        <br>
      </vx-card>
    </div>
    <el-dialog
      v-if="customer"
      :visible.sync="dialogVisible"
      :title="'Add Contacts for ' + customer.business_name"
      width="90%">
      <add-customer-contacts :customer-contacts="customer.customer_contacts" :customer="customer" @save="updateContact" />
    </el-dialog>
  </div>
</template>

<script>
import moment from 'moment';
import checkPermission from '@/utils/permission';
import AddCustomerContacts from './AddCustomerContacts';
import 'swiper/dist/css/swiper.min.css';
import { swiper, swiperSlide } from 'vue-awesome-swiper';
import Resource from '@/api/resource';
const customerDetailsResource = new Resource('customers/details');
export default {
  components: { swiper, swiperSlide, AddCustomerContacts },
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
      columns: [
        'invoice_no',
        'amount_due',
        'amount_paid',
        'payment_status',
        'delivery_status',
        'created_at',
      ],

      options: {
        headings: {
          invoice_no: 'Invoice No.',
          amount_due: 'Amount',
          amount_paid: 'Amount Paid',
          payment_status: 'Payment Status',
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
        filterable: ['invoice_no'],
      },
      swiperOption: {
        slidesPerView: 1.1,
        spaceBetween: 5,
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        },
      },
      schedules: [],
      loader: false,
      dialogVisible: false,
      removing_contact: false,

    };
  },
  mounted() {
    this.fetchDetails();
  },
  methods: {
    moment,
    checkPermission,

    // loadMap() {
    //   this.$refs.mapRef.$mapPromise.then((map) => {
    //     map.panTo({ lat: this.customer.latitude, lng: this.customer.longitude });
    //   });
    // },
    verifyCustomer(id) {
      const app = this;
      const storeResource = new Resource('customers/verify');
      app.$confirm('Click OK to confirm customer verification', 'Warning', {
        confirmButtonText: 'OK',
        cancelButtonText: 'Cancel',
        type: 'warning',
      }).then(() => {
        app.loader = true;
        storeResource.update(id)
          .then(response => {
            app.customer = response.customer;
            app.loader = false;
          });
      }).catch(() => {});
    },
    updateContact(contacts) {
      const app = this;
      app.customer.customer_contacts = contacts;
      app.dialogVisible = false;
    },
    removeContact(contact, index) {
      const app = this;
      if (confirm('Click OK to confirm that you want to remove ' + contact.name.toUpperCase() + ' from list of contacts')) {
        app.removing_contact = true;
        const removeContactResource = new Resource('customers/remove-customer-contact');
        removeContactResource.destroy(contact.id)
          .then(() => {
            app.removing_contact = false;
            app.$message('Contact removed successfully');
            app.customer.customer_contacts.splice(index, 1);
          }).catch(() => {
            app.removing_contact = false;
          });
      }
    },
    fetchDetails() {
      const id = this.$route.params && this.$route.params.id;
      this.loader = true;
      // const param = { customer_id: this.customer.id };
      customerDetailsResource
        .get(id)
        .then((response) => {
          this.schedules = response.schedules;
          this.customer = response.customer;
          this.center = { lat: this.customer.latitude, lng: this.customer.longitude };
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
