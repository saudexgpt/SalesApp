<template>
  <el-card v-if="user.name">
    <el-tabs v-model="activeActivity">
      <!-- <el-tab-pane label="Activities" name="first">
        <div class="block">
          <legend>{{ user.name }}'s Activity Trail</legend>
          <el-timeline style="height: 400px; overflow:auto;">
            <el-timeline-item v-for="(activity_log, index) in user.activity_logs" :key="index" :timestamp="moment(activity_log.created_at).fromNow()" placement="top">
              <el-card>
                <label>{{ activity_log.data.title }}</label>
                <p>{{ activity_log.data.description }}</p>
              </el-card>
            </el-timeline-item>
          </el-timeline>
        </div>
      </el-tab-pane> -->
      <el-tab-pane v-loading="updating" v-if="user.can_edit" label="Update Profile" name="first">
        <div label="First Name">
          <strong>First Name</strong>
          <el-input v-model="user.first_name" :disabled="!user.can_edit" />
        </div>
        <div label="Last Name">
          <strong>Last Name</strong>
          <el-input v-model="user.last_name" :disabled="!user.can_edit" />
        </div>
        <div label="Email">
          <strong>Email</strong>
          <el-input v-model="user.email" :disabled="!user.can_edit" />
        </div>
        <div label="Phone">
          <strong>Phone</strong>
          <el-input v-model="user.phone" :disabled="!user.can_edit" />
        </div>
        <div>
          <el-button :disabled="!user.can_edit" type="primary" @click="onSubmit">
            Update
          </el-button>
        </div>
      </el-tab-pane>
      <el-tab-pane v-loading="updating" v-if="user.can_edit" label="Update Password" name="second">
        <div label="Email">
          <strong>Email</strong>
          <el-input v-model="user.email" :disabled="true" />
        </div>
        <div label="Password">
          <strong>New Password</strong>
          <el-input v-model="user.password" :disabled="!user.can_edit" type="password" />
        </div>
        <div label="Confirm Password">
          <strong>Confirm Password</strong>
          <el-input v-model="user.confirmPassword" :disabled="!user.can_edit" type="password" />
        </div>
        <div>
          <el-button :disabled="!user.can_edit" type="primary" @click="updatePassword">
            Update
          </el-button>
        </div>
      </el-tab-pane>
    </el-tabs>
  </el-card>
</template>

<script>
import moment from 'moment';
import Resource from '@/api/resource';
const userResource = new Resource('users');
const userPasswordResource = new Resource('users/update-password');
export default {
  props: {
    user: {
      type: Object,
      default: () => {
        return {
          name: '',
          email: '',
          avatar: '',
          roles: [],
        };
      },
    },
  },
  data() {
    return {
      activeActivity: 'first',
      updating: false,
      form: {},
    };
  },
  created() {
    this.form = this.user;
  },
  methods: {
    moment,
    onSubmit() {
      this.updating = true;
      userResource
        .update(this.user.id, this.user)
        .then(response => {
          this.updating = false;
          this.$message({
            message: 'User information has been updated successfully',
            type: 'success',
            duration: 5 * 1000,
          });
        })
        .catch(error => {
          console.log(error);
          this.updating = false;
        });
    },
    updatePassword() {
      this.updating = true;
      userPasswordResource
        .update(this.user.id, this.user)
        .then(response => {
          this.updating = false;
          this.user = response.data;
          this.$message({
            message: 'Password has been updated successfully',
            type: 'success',
            duration: 5 * 1000,
          });
        })
        .catch(error => {
          console.log(error);
          this.updating = false;
        });
    },
  },
};
</script>

<style lang="scss" scoped>
.user-activity {
  .user-block {
    .username, .description {
      display: block;
      margin-left: 50px;
      padding: 2px 0;
    }
    img {
      width: 40px;
      height: 40px;
      float: left;
    }
    :after {
      clear: both;
    }
    .img-circle {
      border-radius: 50%;
      border: 2px solid #d2d6de;
      padding: 2px;
    }
    span {
      font-weight: 500;
      font-size: 12px;
    }
  }
  .post {
    font-size: 14px;
    border-bottom: 1px solid #d2d6de;
    margin-bottom: 15px;
    padding-bottom: 15px;
    color: #666;
    .image {
      width: 100%;
    }
    .user-images {
      padding-top: 20px;
    }
  }
  .list-inline {
    padding-left: 0;
    margin-left: -5px;
    list-style: none;
    li {
      display: inline-block;
      padding-right: 5px;
      padding-left: 5px;
      font-size: 13px;
    }
    .link-black {
      &:hover, &:focus {
        color: #999;
      }
    }
  }
  .el-carousel__item h3 {
    color: #475669;
    font-size: 14px;
    opacity: 0.75;
    line-height: 200px;
    margin: 0;
  }

  .el-carousel__item:nth-child(2n) {
    background-color: #99a9bf;
  }

  .el-carousel__item:nth-child(2n+1) {
    background-color: #d3dce6;
  }
}
</style>
