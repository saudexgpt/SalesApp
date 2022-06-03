<template>
  <div v-if="visit">
    <vx-card class="mb-base">
      <!-- Avatar -->
      <div class="vx-row">

        <!-- Avatar Col -->
        <div id="avatar-col" class="vx-col">
          <div class="img-container mb-4">
            <img :src="visit.customer.photo" class="rounded w-full" >
          </div>
        </div>

        <!-- Information - Col 1 -->
        <div id="account-info-col-1" class="vx-col flex-1">
          <table class="table table-striped">
            <tbody>
              <tr>
                <td class="font-semibold">Name:</td>
                <td>{{ visit.customer.business_name }}</td>
              </tr>
              <!-- <tr>
                <td class="font-semibold">Customer Type:</td>
                <td>{{ (visit.customer.visit.customer_type) ? visit.customer.visit.customer_type.name : '' }}</td>
              </tr> -->
              <tr>
                <td class="font-semibold">Address:</td>
                <td>{{ visit.customer.address }}</td>
              </tr>

            </tbody>
          </table>
        </div>
        <!-- /Information - Col 1 -->

        <!-- Information - Col 2 -->
        <div id="account-info-col-2" class="vx-col flex-1">
          <table class="table table-striped">
            <tbody>
              <!-- <tr>
                <td class="font-semibold">Area:</td>
                <td>{{ visit.customer.area }}</td>
              </tr> -->
              <tr>
                <td class="font-semibold">LGA:</td>
                <td>{{ (visit.customer.lga) ? visit.customer.lga.name : '' }}</td>
              </tr>
              <tr>
                <td class="font-semibold">State:</td>
                <td>{{ (visit.customer.state) ? visit.customer.state.name : '' }}</td>
              </tr>

            </tbody>

          </table>
        </div>
      </div>

    </vx-card>
    <div class="vx-row">
      <div class="vx-col lg:w-1/2 w-full">
        <vx-card class="mb-base">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="ThumbsUpIcon" class="mr-2" />
            <span class="font-medium text-lg leading-none">Detailing</span>
          </div>
          <vs-divider />
          <div class="block overflow-x-auto">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Rating</th>
                </tr>
              </thead>
              <tbody>

                <tr v-for="(detail, index) in visit.detailings" :key="index">
                  <template v-if="detail.item !== null">

                    <td class="px-3 py-2">{{ (detail.item) ? detail.item.name : '' }}</td>
                    <td class="px-3 py-2">
                      <el-progress :text-inside="true" :stroke-width="20" :percentage="detail.ratings * 10" :color="ratingStatus(detail.ratings * 10)" />
                    </td>
                  </template>
                </tr>
              </tbody>
            </table>
          </div>
        </vx-card>
        <vx-card class="mb-base">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="ArchiveIcon" class="mr-2" />
            <span class="font-medium text-lg leading-none">Customer Stock Balance</span>
          </div>
          <vs-divider />
          <div class="block overflow-x-auto">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Quantity</th>
                </tr>
              </thead>
              <tbody>

                <tr v-for="(balance, index) in visit.customer_stock_balances" :key="index">
                  <td class="px-3 py-2">{{ balance.item.name }}</td>
                  <td class="px-3 py-2">
                    {{ balance.quantity }} {{ balance.item.package_type }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </vx-card>
        <vx-card class="mb-base">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="GiftIcon" class="mr-2" />
            <span class="font-medium text-lg leading-none">Samples Given</span>
          </div>
          <vs-divider />
          <div class="block overflow-x-auto">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Quantity</th>
                  <!-- <th>Amount</th> -->
                </tr>
              </thead>
              <tbody>

                <tr v-for="(sample, index) in visit.customer_samples" :key="index">
                  <td class="px-3 py-2">{{ sample.item.name }}</td>
                  <td class="px-3 py-2">
                    {{ sample.quantity }} {{ sample.packaging }}
                  </td>
                  <!-- <td class="px-3 py-2">
                    ₦ {{ sample.amount.toLocaleString() }}
                  </td> -->
                </tr>
              </tbody>
            </table>
          </div>
        </vx-card>
      </div>

      <div class="vx-col lg:w-1/2 w-full">
        <vx-card class="mb-base">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
            <span class="font-medium text-lg leading-none">Personnel Contacted</span>
          </div>
          <vs-divider />
          <div class="block overflow-x-auto">
            <div v-if="visit.contact" id="account-info-col-1" class="vx-col flex-1">
              <table class="table table-striped">
                <tbody>
                  <tr>
                    <td class="font-semibold">Name:</td>
                    <td>{{ visit.contact.name }}</td>
                  </tr>
                  <tr>
                    <td class="font-semibold">Primary Phone:</td>
                    <td>{{ visit.contact.phone1 }}</td>
                  </tr>
                  <tr>
                    <td class="font-semibold">WhatsApp:</td>
                    <td>{{ visit.contact.phone2 }}</td>
                  </tr>
                  <tr>
                    <td class="font-semibold">Gender:</td>
                    <td>{{ visit.contact.gender }}</td>
                  </tr>
                  <tr>
                    <td class="font-semibold">DOB:</td>
                    <td>{{ visit.contact.dob }}</td>
                  </tr>
                  <tr>
                    <td class="font-semibold">Specialty/Role:</td>
                    <td>{{ visit.contact.role }}</td>
                  </tr>

                </tbody>
              </table>
            </div>
            <div v-else>
              <el-empty description="Data not found" />
            </div>
          </div>
        </vx-card>
        <vx-card class="mb-base">
          <div class="flex items-end px-3">
            <feather-icon svg-classes="w-6 h-6" icon="UsersIcon" class="mr-2" />
            <span class="font-medium text-lg leading-none">Visit Partner</span>
          </div>
          <vs-divider />
          <div class="block overflow-x-auto">
            <div v-if="visit.visit_partner" id="account-info-col-1" class="vx-col flex-1">
              <table class="table table-striped">
                <tbody>
                  <tr>
                    <td class="font-semibold">Name:</td>
                    <td>{{ visit.visit_partner.name }}</td>
                  </tr>
                  <tr>
                    <td class="font-semibold">Phone:</td>
                    <td>{{ visit.visit_partner.phone }}</td>
                  </tr>

                </tbody>
              </table>
            </div>
            <div v-else>
              <el-empty description="Data not found" />
            </div>
          </div>
        </vx-card>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  props: {
    visit: {
      type: Object,
      default: () => {},
    },
  },
  methods: {
    ratingStatus(ratings) {
      if (ratings < 30) {
        return '#C03639';
      } else if (ratings < 50) {
        return '#e6a23c';
      } else if (ratings < 70) {
        return '#909399';
      } else if (ratings < 90) {
        return '#409eff';
      } else {
        return '#67c23a';
      }
    },
  },
};
</script>
<style lang="scss">
#avatar-col {
  width: 10rem;
}
</style>
