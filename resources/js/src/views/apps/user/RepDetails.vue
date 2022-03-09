
<template>
  <div id="user-customer">
    <div v-if="selectedRep">
      <vx-card class="mb-base">
        <!-- Avatar -->
        <div class="vx-row">

          <!-- Avatar Col -->
          <div id="avatar-col" class="vx-col">
            <div class="img-container mb-4" @click="showFullPhoto = true">
              <img :src="'/'+selectedRep.photo" class="rounded w-full" >
            </div>
          </div>
          <vs-popup
            :active.sync="showFullPhoto"
            :title="selectedRep.name + '\'s  Photo'">
            <div class="con-exemple-prompt">
              <img :src="'/'+selectedRep.photo" class="rounded w-full" >
            </div>
          </vs-popup>

          <!-- Information - Col 1 -->
          <div id="account-info-col-1" class="vx-col flex-1">
            <table class="table table-striped">
              <tbody>
                <tr>
                  <td class="font-semibold">Name:</td>
                  <td>{{ selectedRep.name }}</td>
                </tr>
                <tr>
                  <td class="font-semibold">Email:</td>
                  <td>{{ selectedRep.email }}</td>
                </tr>
                <tr>
                  <td class="font-semibold">Phone:</td>
                  <td>{{ selectedRep.phone }}</td>
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
              <feather-icon svg-classes="w-6 h-6" icon="ThumbsUpIcon" class="mr-2" />
              <span class="font-medium text-lg leading-none">Assigned Cutomers</span>
            </div>
            <vs-divider />
            <div class="block overflow-x-auto">
              <v-client-table
                v-model="selectedRep.customers"
                :columns="['business_name', 'address', 'area', 'action']"
                :options="{}"
              >
                <template slot="action" slot-scope="scope">
                  <el-tooltip
                    class="item"
                    effect="dark"
                    content="View Customer Details"
                    placement="top-start"
                  >
                    <router-link
                      :to="'/customer/details/' + scope.row.id"
                    >
                      <el-button
                        round
                        type="success"
                        size="small"
                        icon="el-icon-view"
                      />
                    </router-link>
                  </el-tooltip>
                  <el-tooltip
                    class="item"
                    effect="dark"
                    content="View Customer Statement"
                    placement="top-start"
                  >
                    <router-link
                      :to="'/report/customer-statement/' + scope.row.id"
                    >
                      <el-button
                        round
                        type="warning"
                        size="small"
                        icon="el-icon-document"
                      />
                    </router-link>
                  </el-tooltip>
                  <!-- <el-tooltip
            class="item"
            effect="dark"
            content="Edit Customer"
            placement="top-start"
          >
            <router-link
              :to="'/administrator/users/edit/' + scope.row.id"
            >
              <el-button
                v-permission="['update-users']"
                round
                type="primary"
                size="small"
                icon="el-icon-edit"
              />
            </router-link>
          </el-tooltip> -->
                </template>
              </v-client-table>
            </div>
          </vx-card>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import moment from 'moment';
import checkPermission from '@/utils/permission';
import 'swiper/dist/css/swiper.min.css';
import { swiper, swiperSlide } from 'vue-awesome-swiper';
export default {
  components: { swiper, swiperSlide },
  props: {
    selectedRep: {
      type: Object,
      default: () => (null),
    },
  },
  data() {
    return {
      showFullPhoto: false,

    };
  },
  methods: {
    moment,
    checkPermission,
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
