<template>
  <router-view v-if="isPublic" />
  <div v-else class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-top">
        <div class="logo-area">
          <div class="logo-icon">📅</div>
          <div class="logo-text">
            <div class="logo-name">EventCheckin</div>
            <div class="logo-sub">Meeting Management</div>
          </div>
        </div>
        <nav class="nav">
          <router-link to="/dashboard" class="nav-item" :class="{ active: $route.path == '/dashboard' }">🖥️ ພາບລວມ (Dashboard)</router-link>
          <router-link to="/meetings" class="nav-item" :class="{ active: $route.path.includes('/meetings') }">🗓️ ຈັດການກອງປະຊຸມ</router-link>
          <router-link to="/checkins" class="nav-item" :class="{ active: $route.path.includes('/checkins') }">✔️ ລົງທະບຽນ (Check-in)</router-link>
          <router-link to="/users" class="nav-item" :class="{ active: $route.path.includes('/users') }">👥 ຈັດການຜູ້ໃຊ້</router-link>
        </nav>
      </div>
      <div class="bottom"><button @click="logout" class="logout">↪ ອອກຈາກລະບົບ</button></div>
    </aside>
    <div class="main-wrap">
      <header class="topbar">
        <div><h3>{{ title }}</h3><small>ລະບົບຈັດການກອງປະຊຸມ ແລະ ເຊັກອິນ</small></div>
        <div class="admin-badge"><span class="dot"></span>{{ authStore.user?.name || "System Admin" }}<span class="pill">ADMIN</span></div>
      </header>
      <div class="page-content"><router-view /></div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from "../../stores/auth.js";
import { useRouter, useRoute } from "vue-router";
import { computed } from "vue";
export default {
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    const route = useRoute();
    const isPublic = computed(() => route.path.startsWith("/checkin/") || route.path == "/login");
    const title = computed(() => {
      if (route.path.includes("dashboard")) return "ພາບລວມລະບົບ (Dashboard)";
      if (route.path.includes("meetings")) return "ຈັດການກອງປະຊຸມ";
      if (route.path.includes("checkins")) return "ຕິດຕາມ Check-in";
      if (route.path.includes("users")) return "ຈັດການຜູ້ໃຊ້";
      return "";
    });
    const logout = async () => { await authStore.logout(); router.push("/login"); };
    return { authStore, logout, isPublic, title };
  },
};
</script>

<style>
/* FIXED GAP - ชิดกัน */
.admin-layout{display:flex; min-height:100vh; background:#f6f8fb; font-family:"Noto Sans Lao",sans-serif;}
.sidebar{width:260px; background:#fff; border-right:1px solid #e5e7eb; position:fixed; height:100vh; display:flex; flex-direction:column; justify-content:space-between; z-index:20;}
.sidebar-top{display:flex; flex-direction:column;}
.logo-area{display:flex; gap:10px; padding:14px 16px 10px 16px; align-items:center;}
.logo-icon{width:38px; height:38px; background:#2563eb; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:16px; flex-shrink:0;}
.logo-text{line-height:1.1;}
.logo-name{font-weight:800; color:#1e40af; font-size:18px; margin:0; line-height:1.1;}
.logo-sub{font-size:10px; color:#94a3b8; margin:0; line-height:1.1; margin-top:1px;}
.nav{display:flex; flex-direction:column; padding:4px 10px 0 10px; gap:3px;}
.nav-item{padding:11px 14px; border-radius:8px; color:#475569; text-decoration:none; font-size:14px; font-weight:500; line-height:1.2;}
.nav-item.active{background:#2563eb; color:#fff !important;}
.bottom{padding:10px; border-top:1px solid #f1f5f9;}
.logout{width:100%; border:none; background:#fef2f2; color:#e11d48; padding:8px; border-radius:8px; font-size:12px;}
.main-wrap{margin-left:260px; flex:1; min-height:100vh;}
.topbar{display:flex; justify-content:space-between; align-items:center; background:#fff; border-bottom:1px solid #e5e7eb; padding:12px 24px; position:sticky; top:0; z-index:10;}
.page-content{padding:18px 20px;}
.admin-badge{border:1px solid #e5e7eb; padding:5px 12px; border-radius:20px; font-size:12px; display:flex; gap:6px; align-items:center; background:#fff;}
.dot{width:7px; height:7px; background:#10b981; border-radius:50%; display:inline-block;}
.pill{background:#ede9fe; color:#7c3aed; padding:2px 7px; border-radius:10px; font-size:10px;}
</style>
