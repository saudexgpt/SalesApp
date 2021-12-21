<template>
  <div class="app-container">
    <div class="block">
      <legend>Notifications</legend>
      <el-timeline v-loading="listLoading" style="height: 400px; overflow:auto;">
        <el-timeline-item v-for="(activity_log, index) in notifications" :key="index" :timestamp="moment(activity_log.created_at).fromNow()" placement="top">
          <el-card>
            <label>{{ activity_log.data.title }}</label>
            <p>{{ activity_log.data.description }}</p>
          </el-card>
        </el-timeline-item>
      </el-timeline>
    </div>
  </div>
</template>

<script>
import moment from 'moment';
import Resource from '@/api/resource';
const markNotificationAsRead = new Resource('notification/mark-as-read');
export default {
  data() {
    return {
      show_notification: true,
      listLoading: false,
    };
  },
  computed: {
    notifications() {
      return this.$store.getters.notifications;
    },
  },
  created() {
    this.markAsRead();
  },
  methods: {
    moment,
    markAsRead() {
      const app = this;
      app.listLoading = true;
      markNotificationAsRead.list()
        .then(response => {
          app.$store.dispatch('user/setNotifications', response.notifications);
          app.$store.dispatch('user/setUnreadNotificationCount', response.unread_notifications);
          this.listLoading = false;
        });
    },
  },
};
</script>
