<template>
  <!-- NOTIFICATIONS -->
  <vs-dropdown vs-custom-content vs-trigger-click class="cursor-pointer">

    <audio id="myAudio">
      <source src="/alert.mp3" type="audio/mpeg">
    </audio>
    <div style="display: none">
      <button id="play_audio" @click="playAudio()">Play Audio</button>
    </div>
    <feather-icon :badge="unreadNotificationCount" icon="BellIcon" class="cursor-pointer mt-1 sm:mr-6 mr-2" />

    <vs-dropdown-menu class="notification-dropdown dropdown-custom vx-navbar-dropdown">

      <div class="notification-top text-center p-5 bg-dark text-white">
        <strong class="text-white">Recent Notifications </strong>
      </div>

      <component ref="mainSidebarPs" :is="scrollbarTag" :key="$vs.rtl" :settings="settings" class="scroll-area--nofications-dropdown p-0 mb-10">
        <ul class="bordered-items">
          <li v-for="ntf in notifications" :key="ntf.index" class="flex justify-between px-4 py-4 notification cursor-pointer">
            <div class="flex items-start">
              <feather-icon :svg-classes="['text-success', 'stroke-current mr-1 h-6 w-6']" icon="InfoIcon"/>
              <div class="mx-2">
                <span :class="['text-primary']" class="font-medium block notification-title">{{ ntf.data.title }}</span>
                <small>{{ ntf.data.description }}</small>
              </div>
            </div>
            <small class="mt-1 whitespace-no-wrap">{{ moment(ntf.created_at).fromNow() }}</small>
          </li>
        </ul>
      </component>

      <div
        class="
        checkout-footer
        fixed
        bottom-0
        rounded-b-lg
        text-primary
        w-full
        p-2
        font-semibold
        text-center
        border
        border-b-0
        border-l-0
        border-r-0
        border-solid
        d-theme-border-grey-light
        cursor-pointer"
        @click="loadNotifications">
        <span>View All Notifications</span>
      </div>
    </vs-dropdown-menu>
  </vs-dropdown>
</template>

<script>
import moment from 'moment';
import VuePerfectScrollbar from 'vue-perfect-scrollbar';
import Pusher from 'pusher-js';
import Echo from 'laravel-echo';
import Resource from '@/api/resource';
export default {
  components: {
    VuePerfectScrollbar,
  },
  data() {
    return {
      settings: {
        maxScrollbarLength: 60,
        wheelSpeed: 0.60,
      },
    };
  },
  computed: {
    unreadNotificationCount() {
      return this.$store.getters.unreadNotificationCount;
    },
    notifications() {
      return this.$store.getters.notifications;
    },
    scrollbarTag() {
      return this.$store.getters.scrollbarTag;
    },
    listenForChanges() {
      window.Pusher = Pusher;
      window.Echo = new Echo({
        broadcaster: 'pusher',
        key: process.env.MIX_PUSHER_APP_KEY,
        cluster: process.env.MIX_PUSHER_APP_CLUSTER,
        encrypted: true,
        auth: {
          headers: {
            Authorization: 'Bearer ' + this.$store.getters.token,
          },
        },
      });
      const currentUserId = this.$store.getters.userId;
      // console.log(currentUserId);
      return window.Echo.private('App.Models.User.' + currentUserId)
        .notification((notification) => {
          // this.playAudio();
          // console.log(this.$refs.play_audio);
          document.getElementById('play_audio').click();
          this.pushNotification(notification);
          this.$notify({
            title: notification.title,
            message: notification.description,
            type: 'success',
            duration: 10000,
          });
        });
    },
  },
  created() {
    this.fetchUserNotifications();
    this.listenForChanges;
  },
  methods: {
    moment,
    fetchUserNotifications() {
      const app = this;
      const userNotifications = new Resource('user-notifications');
      userNotifications.list().then((response) => {
        app.$store.dispatch('user/setNotifications', response.notifications);
        app.$store.dispatch('user/setUnreadNotificationCount', response.unread_notifications);
      });
    },
    pushNotification(notification) {
      const data = {
        title: notification.title,
        description: notification.description,
      };
      notification.data = data;
      this.$store.dispatch('user/addNewNotifications', notification);
    },
    playAudio() {
      var audio = document.getElementById('myAudio');
      audio.play();
    },
    loadNotifications() {
      this.show_notification = false;
      // then update notifications as read
      this.$router.push('/notifications');
    },
  },
};

</script>

